<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\DealerPrice;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\StockLevel;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;

function cartProduct(array $overrides = []): Product
{
    test()->seed(UnitSeeder::class);
    $brand = Brand::query()->firstOrCreate(['name' => 'Faber'], ['is_active' => true]);
    $category = Category::query()->firstOrCreate(['name' => 'Kalem'], ['is_active' => true]);
    $unit = Unit::query()->where('name', $overrides['unit'] ?? 'Adet')->first();
    unset($overrides['unit']);

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

function cartShopper(?Dealer $dealer = null): User
{
    $dealer ??= Dealer::factory()->approved()->create();
    $user = actingAsRole(Role::Dealer);
    $user->forceFill(['dealer_id' => $dealer->id])->save();

    return $user->fresh();
}

test('guests staff and inactive dealers cannot open the catalog or cart', function () {
    $this->get(route('catalog.index'))->assertRedirect(route('login'));

    $admin = actingAsRole(Role::Admin);
    $warehouse = actingAsRole(Role::Warehouse);
    $pending = cartShopper(Dealer::factory()->create());

    $this->actingAs($admin)->get(route('catalog.index'))->assertForbidden();
    $this->actingAs($warehouse)->get(route('cart.index'))->assertForbidden();
    $this->actingAs($pending)->get(route('catalog.index'))->assertForbidden();
});

test('the catalog shows the resolved sale price and hides cost and stock counts', function () {
    $product = cartProduct();
    $hidden = cartProduct(['sku' => 'GIZLI', 'name' => 'Gizli Ürün', 'is_active' => false]);
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '5.00', 'is_active' => true]);
    PriceListItem::query()->create([
        'price_list_id' => $list->id,
        'product_id' => $product->id,
        'price' => '80.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '10.00',
        'is_active' => true,
    ]);
    $dealer = Dealer::factory()->approved()->create(['price_list_id' => $list->id]);
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 8642,
        'reserved_stock' => 0,
    ]);

    $this->actingAs(cartShopper($dealer))->get(route('catalog.index'))
        ->assertOk()
        ->assertSee('Tükenmez Kalem')
        ->assertSee('72,00')
        ->assertSee('86,40')
        ->assertSee(__('In stock'))
        ->assertDontSee('Gizli Ürün')
        ->assertDontSee('8642')
        ->assertDontSee('100,00')
        ->assertDontSee('40,00')
        ->assertDontSee(__('Purchase price'))
        ->assertDontSee($hidden->sku);
});

test('quick order accepts a sku or barcode and rejects an unknown code', function () {
    $product = cartProduct();
    ProductBarcode::query()->create(['product_id' => $product->id, 'barcode' => '8690000000011']);
    $user = cartShopper();

    $this->actingAs($user)->post(route('catalog.quick-order'), [
        'code' => 'KLM-001',
        'quantity' => 2,
    ])->assertRedirect()->assertSessionHas('status');

    $this->actingAs($user)->post(route('catalog.quick-order'), [
        'code' => '8690000000011',
        'quantity' => 1,
    ])->assertRedirect();

    expect(CartItem::query()->first()->quantity)->toBe(3);

    $this->actingAs($user)->post(route('catalog.quick-order'), [
        'code' => 'YOK',
        'quantity' => 1,
    ])->assertSessionHasErrors('code');
});

test('a posted price is ignored and stock is not reserved', function () {
    $product = cartProduct();
    $user = cartShopper();
    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 10,
        'reserved_stock' => 0,
    ]);

    $this->actingAs($user)->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => '0.01',
    ])->assertRedirect();

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('100,00')
        ->assertSee('120,00')
        ->assertDontSee('0,01');

    expect(StockLevel::query()->first()->reserved_stock)->toBe(0)
        ->and(StockLevel::query()->first()->physical_stock)->toBe(10);
});

test('line vat is split once and a higher piece tier replaces the unit price', function () {
    $product = cartProduct([
        'sale_price' => '10.00',
        'prices_include_vat' => true,
        'sku' => 'DAHIL',
        'name' => 'Dahil kalem',
    ]);
    $tiered = cartProduct();
    $dealer = Dealer::factory()->approved()->create();
    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $tiered->id,
        'price' => '90.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $tiered->id,
        'price' => '55.00',
        'minimum_quantity' => 10,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    $user = cartShopper($dealer);

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);
    $this->actingAs($user)->get(route('cart.index'))
        ->assertSee('25,00')
        ->assertSee('30,00')
        ->assertDontSee('24,99');

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $tiered->id, 'quantity' => 1]);
    $item = CartItem::query()->where('product_id', $tiered->id)->first();

    $this->actingAs($user)->patch(route('cart.items.update', $item), ['quantity' => 10])
        ->assertRedirect();

    $this->actingAs($user)->get(route('cart.index'))->assertSee('550,00');
});

test('a pack quantity is converted to pieces before the price tier is chosen', function () {
    $product = cartProduct([
        'unit' => 'Paket',
        'sku' => 'PAK-001',
        'name' => 'Silgi paketi',
        'sale_price' => '50.00',
    ]);
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '0', 'is_active' => true]);
    PriceListItem::query()->create([
        'price_list_id' => $list->id,
        'product_id' => $product->id,
        'price' => '40.00',
        'minimum_quantity' => 20,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    $user = cartShopper(Dealer::factory()->approved()->create(['price_list_id' => $list->id]));

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user)->get(route('cart.index'))->assertSee('50,00')->assertDontSee('40,00');

    $item = CartItem::query()->first();
    $this->actingAs($user)->patch(route('cart.items.update', $item), ['quantity' => 2]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertSee('80,00')
        ->assertSee('20 '.__('pieces'));
});

test('document discount is applied once to the cart gross', function () {
    $product = cartProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '10.00', 'is_active' => true]);
    $user = cartShopper(Dealer::factory()->approved()->create(['price_list_id' => $list->id]));

    $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertSee('100,00')
        ->assertSee('120,00')
        ->assertSee('12,00')
        ->assertSee('108,00')
        ->assertSee(__('Sending the order does not reserve stock.'));
});

test('another dealer cannot see or change the cart and an inactive line drops out of the total', function () {
    $product = cartProduct();
    $owner = cartShopper();
    $this->actingAs($owner)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
    $item = CartItem::query()->first();

    $other = cartShopper();
    $this->actingAs($other)->get(route('cart.index'))
        ->assertOk()
        ->assertDontSee('Tükenmez Kalem');
    $this->actingAs($other)->patch(route('cart.items.update', $item), ['quantity' => 9])->assertNotFound();
    $this->actingAs($other)->delete(route('cart.items.destroy', $item))->assertNotFound();

    $product->update(['is_active' => false]);

    $this->actingAs($owner)->get(route('cart.index'))
        ->assertSee(__('This product is not for sale.'))
        ->assertSee('0,00');

    $this->actingAs($owner)->delete(route('cart.clear'));
    expect(CartItem::query()->count())->toBe(0);
});
