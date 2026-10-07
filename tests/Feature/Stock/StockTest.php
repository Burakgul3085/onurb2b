<?php

use App\Actions\Stock\RecordStockMovement;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Exceptions\StockException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;
use Database\Seeders\WarehouseSeeder;

function stockProduct(): Product
{
    test()->seed(UnitSeeder::class);
    $brand = Brand::query()->create(['name' => 'Faber', 'is_active' => true]);
    $category = Category::query()->create(['name' => 'Kalem', 'is_active' => true]);
    $unit = Unit::query()->where('name', 'Adet')->first();

    return Product::query()->create([
        'sku' => 'KAL-001',
        'name' => 'Mavi tükenmez',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'vat_rate' => '20',
        'purchase_price' => '10.50',
        'sale_price' => '25.00',
        'prices_include_vat' => false,
        'minimum_stock' => 20,
        'critical_stock' => 5,
        'is_active' => true,
    ]);
}

test('the stock list opens on the central warehouse', function () {
    $admin = actingAsRole(Role::Admin);
    Warehouse::query()->create(['name' => 'burak', 'is_active' => true]);
    $this->seed(WarehouseSeeder::class);
    stockProduct();

    $this->actingAs($admin)->get(route('stock.index'))
        ->assertOk()
        ->assertSee('Merkez Depo')
        ->assertSee('value="'.Warehouse::query()->where('name', 'Merkez Depo')->value('id').'" selected', false);
});

test('the central warehouse is seeded', function () {
    $this->seed(WarehouseSeeder::class);

    expect(Warehouse::query()->where('name', 'Merkez Depo')->where('is_active', true)->exists())->toBeTrue();
});

test('guests cannot open stock', function () {
    $this->get(route('stock.index'))->assertRedirect(route('login'));
});

test('available stock is physical minus reserved and a shortfall is refused', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    $record = app(RecordStockMovement::class);

    $record->purchase($warehouse, $product, 10, null, $admin);
    $level = $record->reserve($warehouse, $product, 4);

    expect($level->physical_stock)->toBe(10)
        ->and($level->reserved_stock)->toBe(4)
        ->and($level->available())->toBe(6);

    expect(fn () => $record->damage($warehouse, $product, 7, null, $admin))->toThrow(StockException::class);

    $record->damage($warehouse, $product, 6, null, $admin);
    $level->refresh();

    expect($level->physical_stock)->toBe(4)
        ->and($level->reserved_stock)->toBe(4)
        ->and($level->available())->toBe(0);

    $record->release($warehouse, $product, 4);
    $level->refresh();

    expect($level->available())->toBe(4)
        ->and(StockMovement::query()->count())->toBe(2);
});

test('a count sets the physical quantity and a transfer moves pieces', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $central = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    $second = Warehouse::query()->create(['name' => 'Şube Depo', 'is_active' => true]);
    $record = app(RecordStockMovement::class);

    $record->purchase($central, $product, 100, null, $admin);
    $record->count($central, $product, 80, 'Sayım', $admin);
    $record->transfer($central, $second, $product, 30, null, $admin);

    $centralLevel = StockLevel::query()->where('warehouse_id', $central->id)->where('product_id', $product->id)->first();
    $secondLevel = StockLevel::query()->where('warehouse_id', $second->id)->where('product_id', $product->id)->first();

    expect($centralLevel->physical_stock)->toBe(50)
        ->and($centralLevel->available())->toBe(50)
        ->and($secondLevel->physical_stock)->toBe(30)
        ->and($secondLevel->available())->toBe(30)
        ->and(StockMovement::query()->where('type', StockMovementType::Transfer)->count())->toBe(2);
});

test('stock movements cannot be deleted', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);

    app(RecordStockMovement::class)->purchase($warehouse, $product, 5, null, $admin);

    expect(StockMovement::query()->first()->delete())->toBeFalse()
        ->and(StockMovement::query()->count())->toBe(1);
});

test('an administrator records a purchase and sees the available pieces', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $this->seed(WarehouseSeeder::class);
    $warehouse = Warehouse::query()->where('name', 'Merkez Depo')->first();

    $this->actingAs($admin)->post(route('stock.store'), [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'type' => 'purchase',
        'quantity' => 12,
        'note' => 'İrsaliye 14',
    ])->assertRedirect(route('stock.index', ['warehouse_id' => $warehouse->id]));

    $this->actingAs($admin)->get(route('stock.index', ['warehouse_id' => $warehouse->id]))
        ->assertOk()
        ->assertSee('Mavi tükenmez')
        ->assertSee('12')
        ->assertSee('Kritik stok');

    expect(StockLevel::query()->first()->available())->toBe(12);
});

test('a shortage stays on the form and does not change the balance', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);

    $this->actingAs($admin)->post(route('stock.store'), [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'type' => 'damage',
        'quantity' => 3,
    ])->assertSessionHasErrors('quantity');

    expect(StockLevel::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0);
});

test('a warehouse user can adjust stock but cannot create a warehouse', function () {
    $warehouseUser = actingAsRole(Role::Warehouse);
    $product = stockProduct();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);

    $this->actingAs($warehouseUser)->get(route('stock.index'))->assertOk();
    $this->actingAs($warehouseUser)->post(route('warehouses.store'), ['name' => 'Yeni Depo'])->assertForbidden();
    $this->actingAs($warehouseUser)->post(route('stock.store'), [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'type' => 'adjustment',
        'direction' => 'in',
        'quantity' => 4,
    ])->assertRedirect();

    expect(StockLevel::query()->first()->physical_stock)->toBe(4);
});

test('a dealer and finance user cannot open stock', function () {
    $dealerUser = actingAsRole(Role::Dealer);
    $dealerUser->forceFill(['dealer_id' => Dealer::factory()->create()->id])->save();
    $finance = actingAsRole(Role::FinanceOps);

    $this->actingAs($dealerUser)->get(route('stock.index'))->assertForbidden();
    $this->actingAs($dealerUser)->get(route('warehouses.index'))->assertForbidden();
    $this->actingAs($finance)->get(route('stock.index'))->assertForbidden();
});

test('a warehouse that still holds stock cannot be deactivated', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    app(RecordStockMovement::class)->purchase($warehouse, $product, 2, null, $admin);

    $this->actingAs($admin)->patch(route('warehouses.update', $warehouse), [
        'name' => 'Merkez Depo',
        'is_active' => '0',
    ])->assertSessionHasErrors('is_active');

    expect($warehouse->refresh()->is_active)->toBeTrue();
});

test('sale and return movement types are not accepted from the form', function () {
    $admin = actingAsRole(Role::Admin);
    $product = stockProduct();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);

    $this->actingAs($admin)->post(route('stock.store'), [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'type' => 'sale',
        'quantity' => 1,
    ])->assertSessionHasErrors('type');
});
