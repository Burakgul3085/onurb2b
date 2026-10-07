<?php

namespace App\Actions\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\StockException;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordStockMovement
{
    public function purchase(Warehouse $warehouse, Product $product, int $pieces, ?string $note, User $actor): StockMovement
    {
        return $this->change($warehouse, $product, StockMovementType::Purchase, $this->positive($pieces), $note, $actor);
    }

    public function damage(Warehouse $warehouse, Product $product, int $pieces, ?string $note, User $actor): StockMovement
    {
        return $this->change($warehouse, $product, StockMovementType::Damage, -$this->positive($pieces), $note, $actor);
    }

    public function adjust(Warehouse $warehouse, Product $product, int $signedPieces, ?string $note, User $actor): StockMovement
    {
        if ($signedPieces === 0) {
            throw new StockException(__('Enter a quantity other than zero.'));
        }

        return $this->change($warehouse, $product, StockMovementType::Adjustment, $signedPieces, $note, $actor);
    }

    public function count(Warehouse $warehouse, Product $product, int $countedPieces, ?string $note, User $actor): StockMovement
    {
        if ($countedPieces < 0) {
            throw new StockException(__('Enter a quantity of at least 0.'));
        }

        return DB::transaction(function () use ($warehouse, $product, $countedPieces, $note, $actor) {
            $this->assertActive($warehouse);
            $level = $this->lockLevel($warehouse->id, $product->id);

            return $this->write($level, $warehouse, $product, StockMovementType::Count, $countedPieces - $level->physical_stock, $note, $actor);
        });
    }

    public function transfer(Warehouse $source, Warehouse $destination, Product $product, int $pieces, ?string $note, User $actor): void
    {
        $pieces = $this->positive($pieces);

        if ($source->is($destination)) {
            throw new StockException(__('Choose a different warehouse.'));
        }

        DB::transaction(function () use ($source, $destination, $product, $pieces, $note, $actor) {
            $this->assertActive($source);
            $this->assertActive($destination);

            $firstId = min($source->id, $destination->id);
            $secondId = max($source->id, $destination->id);
            $this->lockLevel($firstId, $product->id);
            $this->lockLevel($secondId, $product->id);

            $group = (string) Str::uuid();
            $sourceLevel = $this->locked($source->id, $product->id);
            $this->write($sourceLevel, $source, $product, StockMovementType::Transfer, -$pieces, $note, $actor, $group, $destination->id);

            $destinationLevel = $this->locked($destination->id, $product->id);
            $this->write($destinationLevel, $destination, $product, StockMovementType::Transfer, $pieces, $note, $actor, $group, $source->id);
        });
    }

    public function sale(Warehouse $warehouse, Product $product, int $pieces, ?string $note, User $actor): StockMovement
    {
        return $this->change($warehouse, $product, StockMovementType::Sale, -$this->positive($pieces), $note, $actor);
    }

    public function receiveReturn(Warehouse $warehouse, Product $product, int $pieces, ?string $note, User $actor): StockMovement
    {
        return $this->change($warehouse, $product, StockMovementType::Return, $this->positive($pieces), $note, $actor);
    }

    public function reserve(Warehouse $warehouse, Product $product, int $pieces): StockLevel
    {
        $pieces = $this->positive($pieces);

        return DB::transaction(function () use ($warehouse, $product, $pieces) {
            $level = $this->lockLevel($warehouse->id, $product->id);

            if ($level->available() < $pieces) {
                throw new StockException(__('There is not enough available stock.'));
            }

            $level->update(['reserved_stock' => $level->reserved_stock + $pieces]);

            return $level->refresh();
        });
    }

    public function release(Warehouse $warehouse, Product $product, int $pieces): StockLevel
    {
        $pieces = $this->positive($pieces);

        return DB::transaction(function () use ($warehouse, $product, $pieces) {
            $level = $this->lockLevel($warehouse->id, $product->id);

            if ($level->reserved_stock < $pieces) {
                throw new StockException(__('There is not enough reserved stock to release.'));
            }

            $level->update(['reserved_stock' => $level->reserved_stock - $pieces]);

            return $level->refresh();
        });
    }

    private function change(Warehouse $warehouse, Product $product, StockMovementType $type, int $delta, ?string $note, User $actor): StockMovement
    {
        return DB::transaction(function () use ($warehouse, $product, $type, $delta, $note, $actor) {
            $this->assertActive($warehouse);
            $level = $this->lockLevel($warehouse->id, $product->id);

            return $this->write($level, $warehouse, $product, $type, $delta, $note, $actor);
        });
    }

    private function write(
        StockLevel $level,
        Warehouse $warehouse,
        Product $product,
        StockMovementType $type,
        int $delta,
        ?string $note,
        User $actor,
        ?string $transferGroup = null,
        ?int $counterpartWarehouseId = null,
    ): StockMovement {
        $next = $level->physical_stock + $delta;

        if ($next < $level->reserved_stock) {
            throw new StockException(__('There is not enough available stock.'));
        }

        $before = $level->physical_stock;
        $level->update(['physical_stock' => $next]);

        return StockMovement::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $delta,
            'physical_before' => $before,
            'physical_after' => $next,
            'counterpart_warehouse_id' => $counterpartWarehouseId,
            'transfer_group' => $transferGroup,
            'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            'user_id' => $actor->id,
        ]);
    }

    private function lockLevel(int $warehouseId, int $productId): StockLevel
    {
        StockLevel::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['physical_stock' => 0, 'reserved_stock' => 0],
        );

        return $this->locked($warehouseId, $productId);
    }

    private function locked(int $warehouseId, int $productId): StockLevel
    {
        return StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertActive(Warehouse $warehouse): void
    {
        if (! $warehouse->is_active) {
            throw new StockException(__('Choose an active warehouse.'));
        }
    }

    private function positive(int $pieces): int
    {
        if ($pieces < 1) {
            throw new StockException(__('Enter a quantity of at least 1.'));
        }

        return $pieces;
    }
}
