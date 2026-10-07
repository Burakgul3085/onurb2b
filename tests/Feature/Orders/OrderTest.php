<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;

function orderProduct(array $overrides = []): Product
{
    test()->seed(UnitSeeder::class);
    $brand = Brand::query()->firstOrCreate(['name' => 'Faber'], ['is_active' => true]);
    $category = Category::query()->firstOrCreate(['name' => 'Kalem'], ['is_active' => true]);
    $unitName = $overrides['unit'] ?? 'Adet';
    unset($overrides['unit']);
    $unit = Unit::query()->where('name', $unitName)->first();

    return Product::query()->create(array_merge([
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
    ], $overrides));
}

function orderShopper(?Dealer $dealer = null): User
{
    $dealer ??= Dealer::factory()->approved()->create();
    $user = actingAsRole(Role::Dealer);
    $user->forceFill(['dealer_id' => $dealer->id])->save();

    return $user->fresh();
}

function orderPayload(Dealer $dealer, array $overrides = []): array
{
    return array_merge([
        'delivery_address' => $dealer->delivery_address,
        'district' => $dealer->district->value,
        'note' => 'Sabah teslim',
    ], $overrides);
}

test('guests cannot open orders', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));
});

test('sending an order locks the price and does not reserve stock', function () {
    $product = orderProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '10.00', 'is_active' => true]);
    $dealer = Dealer::factory()->approved()->create(['price_list_id' => $list->id]);
    $user = orderShopper($dealer);
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 30,
        'reserved_stock' => 0,
    ]);

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user)->post(route('orders.store'), orderPayload($dealer))->assertRedirect();

    $order = Order::query()->first();
    $line = $order->lines()->first();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->number)->toBe('SP-'.now()->year.'-00001')
        ->and($order->payable)->toBe('108.00')
        ->and($order->document_discount_percent)->toBe('10.00')
        ->and($line->unit_price)->toBe('100.00')
        ->and($line->net)->toBe('100.00')
        ->and($line->requested_pieces)->toBe(1)
        ->and($line->approved_pieces)->toBe(0)
        ->and(CartItem::query()->count())->toBe(0)
        ->and(StockLevel::query()->first()->reserved_stock)->toBe(0);

    $product->update(['sale_price' => '1.00']);
    $list->update(['document_discount_percent' => '0']);

    expect($order->refresh()->payable)->toBe('108.00')
        ->and($line->refresh()->unit_price)->toBe('100.00');

    $admin = actingAsRole(Role::Admin);
    $this->actingAs($admin)->post(route('orders.cancel', $order), ['reason' => 'Beklerken iptal'])
        ->assertRedirect();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(StockLevel::query()->first()->reserved_stock)->toBe(0);

    $other = orderShopper();
    $this->actingAs($other)->get(route('orders.show', $order))->assertNotFound();
    $this->actingAs($other)->get(route('orders.index'))->assertDontSee($order->number);
});

test('an empty cart and an unsellable line cannot be sent', function () {
    $product = orderProduct();
    $user = orderShopper();
    $dealer = $user->dealer;

    $this->actingAs($user)->post(route('orders.store'), orderPayload($dealer))
        ->assertSessionHasErrors('order');

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $product->update(['is_active' => false]);

    $this->actingAs($user)->post(route('orders.store'), orderPayload($dealer))
        ->assertSessionHasErrors('order');

    expect(Order::query()->count())->toBe(0)
        ->and(CartItem::query()->count())->toBe(1);
});

test('approval reserves only the accepted pieces and a shortage rolls back', function () {
    $first = orderProduct();
    $second = orderProduct(['sku' => 'KLM-002', 'name' => 'Kurşun kalem']);
    $user = orderShopper();
    $dealer = $user->dealer;
    $admin = actingAsRole(Role::Admin);
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create(['warehouse_id' => $warehouse->id, 'product_id' => $first->id, 'physical_stock' => 10, 'reserved_stock' => 0]);
    StockLevel::query()->create(['warehouse_id' => $warehouse->id, 'product_id' => $second->id, 'physical_stock' => 1, 'reserved_stock' => 0]);

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $first->id, 'quantity' => 6]);
    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $second->id, 'quantity' => 2]);
    $this->actingAs($user)->post(route('orders.store'), orderPayload($dealer));
    $order = Order::query()->first();
    $lines = $order->lines()->get()->keyBy('product_id');

    $this->actingAs($user)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$lines[$first->id]->id => 6, $lines[$second->id]->id => 2],
    ])->assertForbidden();

    $this->actingAs(actingAsRole(Role::Warehouse))->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$lines[$first->id]->id => 6, $lines[$second->id]->id => 2],
    ])->assertForbidden();

    $this->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$lines[$first->id]->id => 6, $lines[$second->id]->id => 2],
    ])->assertSessionHasErrors('approved');

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and(StockLevel::query()->sum('reserved_stock'))->toBe(0);

    $this->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$lines[$first->id]->id => 4, $lines[$second->id]->id => 0],
    ])->assertRedirect();

    expect($order->refresh()->status)->toBe(OrderStatus::Approved)
        ->and($lines[$first->id]->refresh()->approved_pieces)->toBe(4)
        ->and($lines[$second->id]->refresh()->approved_pieces)->toBe(0)
        ->and(StockLevel::query()->where('product_id', $first->id)->value('reserved_stock'))->toBe(4)
        ->and(StockLevel::query()->where('product_id', $first->id)->value('physical_stock'))->toBe(10);
});

test('preparing keeps the reserve and cancellation releases it', function () {
    $product = orderProduct();
    $user = orderShopper();
    $admin = actingAsRole(Role::Admin);
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 8,
        'reserved_stock' => 0,
    ]);

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);
    $this->actingAs($user)->post(route('orders.store'), orderPayload($user->dealer));
    $order = Order::query()->first();
    $line = $order->lines()->first();

    $this->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$line->id => 3],
    ]);
    $this->actingAs($admin)->post(route('orders.prepare', $order))->assertRedirect();

    expect($order->refresh()->status)->toBe(OrderStatus::Preparing)
        ->and(StockLevel::query()->first()->reserved_stock)->toBe(3);

    $this->actingAs($admin)->post(route('orders.cancel', $order), ['reason' => 'Bayi vazgeçti'])
        ->assertRedirect();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(StockLevel::query()->first()->reserved_stock)->toBe(0)
        ->and(StockLevel::query()->first()->physical_stock)->toBe(8);

    $order->update(['status' => OrderStatus::OutForDelivery]);
    $this->actingAs($admin)->post(route('orders.cancel', $order), ['reason' => 'Geç'])
        ->assertSessionHasErrors('reason');
});

test('a pack order reserves pieces and pending cancellation does not touch stock', function () {
    $product = orderProduct(['unit' => 'Paket', 'sku' => 'PAK-001', 'name' => 'Silgi paketi']);
    $user = orderShopper();
    $admin = actingAsRole(Role::Admin);
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 50,
        'reserved_stock' => 0,
    ]);

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
    $this->actingAs($user)->post(route('orders.store'), orderPayload($user->dealer));
    $line = OrderLine::query()->first();

    expect($line->quantity)->toBe(2)
        ->and($line->pieces_per_unit)->toBe(10)
        ->and($line->requested_pieces)->toBe(20);

    $this->actingAs($admin)->post(route('orders.approve', $line->order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$line->id => 20],
    ])->assertRedirect();

    expect(StockLevel::query()->first()->reserved_stock)->toBe(20)
        ->and(StockLevel::query()->first()->physical_stock)->toBe(50)
        ->and($line->order->refresh()->status)->toBe(OrderStatus::Approved);
});
