<?php

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{product: Product, order: Order, line: OrderLine, warehouse: Warehouse, admin: User, shopper: User, driver: User, dealer: Dealer}
 */
function deliveryWorld(): array
{
    test()->seed(UnitSeeder::class);
    $brand = Brand::query()->firstOrCreate(['name' => 'Faber'], ['is_active' => true]);
    $category = Category::query()->firstOrCreate(['name' => 'Kalem'], ['is_active' => true]);
    $unit = Unit::query()->where('name', 'Adet')->first();
    $product = Product::query()->create([
        'sku' => 'KLM-001',
        'name' => 'Tükenmez Kalem',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'vat_rate' => '20',
        'purchase_price' => '40.00',
        'sale_price' => '100.00',
        'prices_include_vat' => false,
        'minimum_stock' => 20,
        'critical_stock' => 5,
        'is_active' => true,
    ]);
    $dealer = Dealer::factory()->approved()->create();
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id])->save();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 30,
        'reserved_stock' => 0,
    ]);
    $admin = actingAsRole(Role::Admin);
    $driver = actingAsRole(Role::Delivery);

    test()->actingAs($shopper)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);
    test()->actingAs($shopper)->post(route('orders.store'), [
        'delivery_address' => $dealer->delivery_address,
        'district' => $dealer->district->value,
        'note' => 'Sabah teslim',
    ]);

    $order = Order::query()->first();
    $line = $order->lines()->first();
    test()->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$line->id => 3],
    ])->assertRedirect();

    return [
        'product' => $product,
        'order' => $order->refresh(),
        'line' => $line->refresh(),
        'warehouse' => $warehouse,
        'admin' => $admin,
        'shopper' => $shopper->fresh(),
        'driver' => $driver,
        'dealer' => $dealer,
    ];
}

function deliveryPlan(array $world, array $overrides = []): Delivery
{
    test()->actingAs($world['admin'])->post(route('deliveries.store'), array_merge([
        'order_id' => $world['order']->id,
        'user_id' => $world['driver']->id,
        'sequence' => 1,
        'scheduled_on' => '2026-10-08',
        'scheduled_time' => '09:30',
    ], $overrides))->assertRedirect();

    return Delivery::query()->latest('id')->first();
}

test('planning a delivery does not move stock or the order', function () {
    $world = deliveryWorld();
    $delivery = deliveryPlan($world);
    $level = StockLevel::query()->first();

    expect($delivery->status)->toBe(DeliveryStatus::Preparing)
        ->and($delivery->province)->toBe('Eskişehir')
        ->and($delivery->district)->toBe($world['order']->district)
        ->and($delivery->delivery_address)->toBe($world['order']->delivery_address)
        ->and($world['order']->refresh()->status)->toBe(OrderStatus::Approved)
        ->and($level->physical_stock)->toBe(30)
        ->and($level->reserved_stock)->toBe(3)
        ->and(StockMovement::query()->count())->toBe(0);

    test()->actingAs($world['admin'])->post(route('deliveries.store'), [
        'order_id' => $world['order']->id,
        'user_id' => $world['driver']->id,
        'sequence' => 2,
        'scheduled_on' => '2026-10-08',
    ])->assertSessionHasErrors('delivery');

    $world['driver']->update(['is_active' => false]);
    $delivery->delete();
    test()->actingAs($world['admin'])->post(route('deliveries.store'), [
        'order_id' => $world['order']->id,
        'user_id' => $world['driver']->id,
        'sequence' => 1,
        'scheduled_on' => '2026-10-08',
    ])->assertSessionHasErrors('delivery');

    expect(Delivery::query()->count())->toBe(0);
});

test('dispatch drops the loaded pieces and leaves the rest reserved', function () {
    $world = deliveryWorld();
    StockLevel::query()->update(['physical_stock' => 3, 'reserved_stock' => 3]);
    $delivery = deliveryPlan($world);

    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 4],
    ])->assertSessionHasErrors('shipped');

    expect(StockLevel::query()->first()->only(['physical_stock', 'reserved_stock']))->toBe([
        'physical_stock' => 3,
        'reserved_stock' => 3,
    ])->and($delivery->refresh()->status)->toBe(DeliveryStatus::Preparing);

    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 2],
    ])->assertRedirect();

    $level = StockLevel::query()->first();

    expect($delivery->refresh()->status)->toBe(DeliveryStatus::OutForDelivery)
        ->and($world['order']->refresh()->status)->toBe(OrderStatus::OutForDelivery)
        ->and($level->physical_stock)->toBe(1)
        ->and($level->reserved_stock)->toBe(1)
        ->and(StockMovement::query()->where('type', StockMovementType::Sale)->count())->toBe(1)
        ->and($delivery->lines()->first()->shipped_pieces)->toBe(2);

    test()->actingAs($world['admin'])->post(route('orders.cancel', $world['order']), [
        'reason' => 'Yolda iptal',
    ])->assertSessionHasErrors('reason');

    expect($world['order']->refresh()->status)->toBe(OrderStatus::OutForDelivery)
        ->and($world['line']->refresh()->delete())->toBeFalse();
});

test('a full delivery keeps the pieces out of stock', function () {
    $world = deliveryWorld();
    $payable = $world['order']->payable;
    $delivery = deliveryPlan($world);

    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 3],
    ]);

    $line = $delivery->lines()->first();
    test()->actingAs($world['admin'])->post(route('deliveries.complete', $delivery), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$line->id => 3],
    ])->assertRedirect();

    $level = StockLevel::query()->first();

    expect($delivery->refresh()->status)->toBe(DeliveryStatus::Delivered)
        ->and($world['order']->refresh()->status)->toBe(OrderStatus::Delivered)
        ->and($world['order']->payable)->toBe($payable)
        ->and($world['line']->refresh()->delivered_pieces)->toBe(3)
        ->and($line->refresh()->delivered_pieces)->toBe(3)
        ->and($level->physical_stock)->toBe(27)
        ->and($level->reserved_stock)->toBe(0)
        ->and(StockMovement::query()->where('type', StockMovementType::Return)->count())->toBe(0);
});

test('a partial delivery returns the gap and a later trip can finish it', function () {
    $world = deliveryWorld();
    $delivery = deliveryPlan($world);
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 3],
    ]);
    $line = $delivery->lines()->first();

    test()->actingAs($world['admin'])->post(route('deliveries.complete', $delivery), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$line->id => 1],
    ])->assertRedirect();

    $level = StockLevel::query()->first();

    expect($world['order']->refresh()->status)->toBe(OrderStatus::PartiallyDelivered)
        ->and($world['line']->refresh()->delivered_pieces)->toBe(1)
        ->and($level->physical_stock)->toBe(29)
        ->and($level->reserved_stock)->toBe(2)
        ->and(StockMovement::query()->where('type', StockMovementType::Return)->count())->toBe(1);

    $second = deliveryPlan($world, ['sequence' => 2, 'scheduled_on' => '2026-10-09']);
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $second), [
        'shipped' => [$world['line']->id => 2],
    ])->assertRedirect();

    expect(StockLevel::query()->first()->only(['physical_stock', 'reserved_stock']))->toBe([
        'physical_stock' => 27,
        'reserved_stock' => 0,
    ]);
});

test('a failed delivery puts the load back on reserve', function () {
    $world = deliveryWorld();
    $delivery = deliveryPlan($world);
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 3],
    ]);

    test()->actingAs($world['admin'])->post(route('deliveries.fail', $delivery), [
        'note' => 'Kapalıydı',
    ])->assertRedirect();

    $level = StockLevel::query()->first();

    expect($delivery->refresh()->status)->toBe(DeliveryStatus::Failed)
        ->and($world['order']->refresh()->status)->toBe(OrderStatus::DeliveryFailed)
        ->and($world['line']->refresh()->delivered_pieces)->toBe(0)
        ->and($level->physical_stock)->toBe(30)
        ->and($level->reserved_stock)->toBe(3)
        ->and(StockMovement::query()->where('type', StockMovementType::Sale)->count())->toBe(1)
        ->and(StockMovement::query()->where('type', StockMovementType::Return)->count())->toBe(1)
        ->and(OrderLine::query()->count())->toBe(1)
        ->and($delivery->delete())->toBeFalse();
});

test('a failure after a successful drop stays partially delivered', function () {
    $world = deliveryWorld();
    $first = deliveryPlan($world);
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $first), [
        'shipped' => [$world['line']->id => 2],
    ]);
    test()->actingAs($world['admin'])->post(route('deliveries.complete', $first), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$first->lines()->first()->id => 2],
    ]);

    $second = deliveryPlan($world, ['sequence' => 2, 'scheduled_on' => '2026-10-09']);
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $second), [
        'shipped' => [$world['line']->id => 1],
    ]);
    test()->actingAs($world['admin'])->post(route('deliveries.fail', $second), [
        'note' => 'Adres yok',
    ])->assertRedirect();

    expect($world['order']->refresh()->status)->toBe(OrderStatus::PartiallyDelivered)
        ->and($world['line']->refresh()->delivered_pieces)->toBe(2)
        ->and(StockLevel::query()->first()->physical_stock)->toBe(28)
        ->and(StockLevel::query()->first()->reserved_stock)->toBe(1);
});

test('delivery access stays with staff and the assigned driver', function () {
    $world = deliveryWorld();
    $delivery = deliveryPlan($world);
    $otherDriver = actingAsRole(Role::Delivery);
    $warehouseUser = actingAsRole(Role::Warehouse);

    test()->actingAs($world['shopper'])->get(route('deliveries.index'))->assertForbidden();
    test()->actingAs($warehouseUser)->get(route('deliveries.index'))->assertForbidden();
    test()->actingAs($otherDriver)->get(route('deliveries.show', $delivery))->assertNotFound();
    test()->actingAs($world['driver'])->get(route('deliveries.show', $delivery))
        ->assertOk()
        ->assertSee($world['order']->number);
    test()->actingAs($world['admin'])->get(route('deliveries.index'))
        ->assertOk()
        ->assertSee($world['driver']->name);

    test()->actingAs($world['shopper'])->get(route('orders.show', $world['order']))
        ->assertOk()
        ->assertSee($world['driver']->name)
        ->assertSee('09:30')
        ->assertDontSee($world['driver']->email);

    $otherDealer = actingAsRole(Role::Dealer);
    $otherDealer->forceFill(['dealer_id' => Dealer::factory()->approved()->create()->id])->save();
    test()->actingAs($otherDealer)->get(route('orders.show', $world['order']))->assertNotFound();

    Storage::fake('public');
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 3],
    ]);
    test()->actingAs($world['admin'])->post(route('deliveries.complete', $delivery), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$delivery->lines()->first()->id => 3],
        'proof' => UploadedFile::fake()->create('kanit.svg', 20, 'image/svg+xml'),
    ])->assertSessionHasErrors('proof');

    test()->actingAs($world['admin'])->post(route('deliveries.complete', $delivery), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$delivery->lines()->first()->id => 3],
        'proof' => UploadedFile::fake()->image('kanit.jpg'),
    ])->assertRedirect();

    expect($delivery->refresh()->proof_path)->not->toBeNull();
    Storage::disk('public')->assertExists($delivery->proof_path);

    test()->actingAs($world['shopper'])->get(route('orders.show', $world['order']))
        ->assertSee('Ayşe');
});

test('only a planned delivery can be removed', function () {
    $world = deliveryWorld();
    $delivery = deliveryPlan($world);

    test()->actingAs($world['admin'])->delete(route('deliveries.destroy', $delivery))->assertRedirect();
    expect(Delivery::query()->count())->toBe(0);

    $delivery = deliveryPlan($world);
    test()->actingAs($world['admin'])->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$world['line']->id => 3],
    ]);
    test()->actingAs($world['admin'])->delete(route('deliveries.destroy', $delivery))
        ->assertSessionHasErrors('delivery');

    expect(Delivery::query()->count())->toBe(1);
});
