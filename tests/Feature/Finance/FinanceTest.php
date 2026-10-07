<?php

use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Finance\LedgerBalance;
use Database\Seeders\UnitSeeder;

/**
 * @return array{product: Product, order: Order, line: OrderLine, admin: User, shopper: User, driver: User, dealer: Dealer, delivery: Delivery}
 */
function financeDelivered(int $quantity = 3, int $termDays = 30, array $dealerOverrides = []): array
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
    $dealer = Dealer::factory()->approved()->create(array_merge(['payment_term_days' => $termDays], $dealerOverrides));
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

    test()->actingAs($shopper)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => $quantity]);
    test()->actingAs($shopper)->post(route('orders.store'), [
        'delivery_address' => $dealer->delivery_address,
        'district' => $dealer->district->value,
        'note' => 'Sabah teslim',
    ]);
    $order = Order::query()->first();
    $line = $order->lines()->first();
    test()->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$line->id => $quantity],
    ]);
    test()->actingAs($admin)->post(route('deliveries.store'), [
        'order_id' => $order->id,
        'user_id' => $driver->id,
        'sequence' => 1,
        'scheduled_on' => now()->toDateString(),
        'scheduled_time' => '09:30',
    ]);
    $delivery = Delivery::query()->first();
    test()->actingAs($admin)->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$line->id => $quantity],
    ]);

    expect(LedgerEntry::query()->count())->toBe(0);

    test()->actingAs($admin)->post(route('deliveries.complete', $delivery), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$delivery->lines()->first()->id => $quantity],
    ])->assertRedirect();

    return [
        'product' => $product,
        'order' => $order->refresh(),
        'line' => $line->refresh(),
        'admin' => $admin,
        'shopper' => $shopper->fresh(),
        'driver' => $driver,
        'dealer' => $dealer->refresh(),
        'delivery' => $delivery->refresh(),
    ];
}

test('a completed delivery posts a debit and a due date without repricing', function () {
    $list = PriceList::query()->create([
        'name' => 'Okullar',
        'document_discount_percent' => '10.00',
        'is_active' => true,
    ]);
    $world = financeDelivered(1, 3, ['price_list_id' => $list->id]);
    $world['dealer']->update(['payment_term_days' => 90]);

    $entry = LedgerEntry::query()->first();
    $payable = $world['order']->payable;

    expect($entry->type)->toBe(LedgerType::Sale)
        ->and($entry->debit)->toBe('108.00')
        ->and($entry->credit)->toBe('0.00')
        ->and($entry->due_on->toDateString())->toBe(now()->addDays(3)->toDateString())
        ->and($payable)->toBe('108.00')
        ->and(app(LedgerBalance::class)->forDealer($world['dealer']->id))->toBe('108.00')
        ->and($entry->delete())->toBeFalse()
        ->and(LedgerEntry::query()->count())->toBe(1);

    $list->update(['document_discount_percent' => '0']);
    $world['dealer']->update(['payment_term_days' => 0]);

    expect($entry->refresh()->debit)->toBe('108.00')
        ->and($entry->due_on->toDateString())->toBe(now()->addDays(3)->toDateString())
        ->and($world['order']->refresh()->payable)->toBe($payable);
});

test('a partial delivery bills only the received pieces', function () {
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
        'minimum_stock' => 1,
        'critical_stock' => 0,
        'is_active' => true,
    ]);
    $dealer = Dealer::factory()->approved()->create();
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id])->save();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'physical_stock' => 30, 'reserved_stock' => 0]);
    $admin = actingAsRole(Role::Admin);
    $driver = actingAsRole(Role::Delivery);
    test()->actingAs($shopper)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);
    test()->actingAs($shopper)->post(route('orders.store'), [
        'delivery_address' => $dealer->delivery_address,
        'district' => $dealer->district->value,
    ]);
    $order = Order::query()->first();
    $line = $order->lines()->first();
    test()->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $warehouse->id,
        'approved' => [$line->id => 3],
    ]);
    test()->actingAs($admin)->post(route('deliveries.store'), [
        'order_id' => $order->id,
        'user_id' => $driver->id,
        'sequence' => 1,
        'scheduled_on' => now()->toDateString(),
    ]);
    $delivery = Delivery::query()->first();
    test()->actingAs($admin)->post(route('deliveries.dispatch', $delivery), ['shipped' => [$line->id => 3]]);
    test()->actingAs($admin)->post(route('deliveries.fail', $delivery), ['note' => 'Kapalı'])->assertRedirect();

    expect(LedgerEntry::query()->count())->toBe(0)
        ->and($order->refresh()->status)->toBe(OrderStatus::DeliveryFailed);

    test()->actingAs($admin)->post(route('deliveries.store'), [
        'order_id' => $order->id,
        'user_id' => $driver->id,
        'sequence' => 2,
        'scheduled_on' => now()->addDay()->toDateString(),
    ]);
    $second = Delivery::query()->latest('id')->first();
    test()->actingAs($admin)->post(route('deliveries.dispatch', $second), ['shipped' => [$line->id => 3]]);
    test()->actingAs($admin)->post(route('deliveries.complete', $second), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$second->lines()->first()->id => 1],
    ])->assertRedirect();

    expect(LedgerEntry::query()->first()->debit)->toBe('120.00')
        ->and(app(LedgerBalance::class)->forDealer($dealer->id))->toBe('120.00');
});

test('collections and returns move the balance and stock without deleting rows', function () {
    $world = financeDelivered();
    $finance = actingAsRole(Role::FinanceOps);
    $warehouseUser = actingAsRole(Role::Warehouse);
    $other = actingAsRole(Role::Dealer);
    $other->forceFill(['dealer_id' => Dealer::factory()->approved()->create()->id])->save();

    expect(app(LedgerBalance::class)->forDealer($world['dealer']->id))->toBe('360.00');

    test()->actingAs($world['shopper'])->get(route('finance.index'))
        ->assertRedirect(route('finance.show', $world['dealer']));
    test()->actingAs($world['shopper'])->get(route('finance.show', $world['dealer']))
        ->assertOk()
        ->assertSee('360,00')
        ->assertDontSee(__('Record collection'));
    test()->actingAs($world['shopper'])->post(route('finance.collections.store', $world['dealer']), [
        'amount' => '10,00',
        'method' => PaymentMethod::Cash->value,
        'document_date' => now()->toDateString(),
    ])->assertForbidden();
    test()->actingAs($other)->get(route('finance.show', $world['dealer']))->assertNotFound();
    test()->actingAs($warehouseUser)->get(route('finance.index'))->assertForbidden();

    test()->actingAs($finance)->post(route('finance.collections.store', $world['dealer']), [
        'amount' => '360,00',
        'method' => PaymentMethod::Eft->value,
        'document_date' => now()->toDateString(),
        'note' => 'EFT geldi',
    ])->assertRedirect();

    $collection = LedgerEntry::query()->where('type', LedgerType::Collection)->first();

    expect(app(LedgerBalance::class)->forDealer($world['dealer']->id))->toBe('0.00')
        ->and($collection->method)->toBe(PaymentMethod::Eft)
        ->and($collection->credit)->toBe('360.00');

    test()->actingAs($finance)->post(route('finance.entries.reverse', $collection))->assertRedirect();

    expect(app(LedgerBalance::class)->forDealer($world['dealer']->id))->toBe('360.00')
        ->and(LedgerEntry::query()->count())->toBe(3)
        ->and($collection->refresh()->delete())->toBeFalse();

    test()->actingAs($world['shopper'])->get(route('orders.show', $world['order']))
        ->assertDontSee(__('Operational return'));

    test()->actingAs($finance)->post(route('orders.returns.store', $world['order']), [
        'document_date' => now()->toDateString(),
        'pieces' => [$world['line']->id => 4],
        'note' => 'Fazla',
    ])->assertSessionHasErrors('pieces');

    expect(StockLevel::query()->first()->physical_stock)->toBe(27)
        ->and($world['line']->refresh()->returned_pieces)->toBe(0);

    test()->actingAs($finance)->post(route('orders.returns.store', $world['order']), [
        'document_date' => now()->toDateString(),
        'pieces' => [$world['line']->id => 1],
        'note' => 'Bir adet',
    ])->assertRedirect();

    $returnEntry = LedgerEntry::query()->where('type', LedgerType::Return)->first();

    expect($returnEntry->credit)->toBe('120.00')
        ->and($world['line']->refresh()->returned_pieces)->toBe(1)
        ->and($world['line']->delivered_pieces)->toBe(3)
        ->and(StockLevel::query()->first()->physical_stock)->toBe(28)
        ->and(StockMovement::query()->where('type', StockMovementType::Return)->count())->toBe(1)
        ->and(app(LedgerBalance::class)->forDealer($world['dealer']->id))->toBe('240.00')
        ->and($world['line']->delete())->toBeFalse();

    test()->actingAs($finance)->post(route('finance.entries.reverse', $returnEntry))->assertRedirect();

    expect($world['line']->refresh()->returned_pieces)->toBe(0)
        ->and(StockLevel::query()->first()->physical_stock)->toBe(27)
        ->and(StockMovement::query()->where('type', StockMovementType::Sale)->count())->toBe(2)
        ->and(StockMovement::query()->where('type', StockMovementType::Return)->count())->toBe(1)
        ->and(app(LedgerBalance::class)->forDealer($world['dealer']->id))->toBe('360.00')
        ->and(OrderLine::query()->count())->toBe(1);

    test()->actingAs($world['admin'])->get(route('finance.index'))
        ->assertOk()
        ->assertSee($world['dealer']->company_name)
        ->assertSee('360,00');
});
