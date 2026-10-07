<?php

use App\Enums\PriceSource;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\DealerPrice;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Pricing\PriceResolver;
use Database\Seeders\UnitSeeder;

function priceProduct(string $sale = '100.00'): Product
{
    test()->seed(UnitSeeder::class);
    $brand = Brand::query()->create(['name' => 'Faber', 'is_active' => true]);
    $category = Category::query()->create(['name' => 'Kalem', 'is_active' => true]);
    $unit = Unit::query()->where('name', 'Adet')->first();

    return Product::query()->create([
        'sku' => 'KLM-001',
        'name' => 'Tükenmez Kalem',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'vat_rate' => '20',
        'purchase_price' => '40.00',
        'sale_price' => $sale,
        'prices_include_vat' => false,
        'minimum_stock' => 20,
        'critical_stock' => 5,
        'is_active' => true,
    ]);
}

function priceDealer(?PriceList $list = null): Dealer
{
    return Dealer::factory()->approved()->create([
        'price_list_id' => $list?->id,
    ]);
}

test('a product without a list or special price uses the general sale price', function () {
    $product = priceProduct();
    $quote = app(PriceResolver::class)->quote(priceDealer(), $product, 1);

    expect($quote->source)->toBe(PriceSource::Product)
        ->and($quote->net)->toBe('100.00')
        ->and($quote->vat)->toBe('20.00')
        ->and($quote->gross)->toBe('120.00')
        ->and($quote->documentDiscountPercent)->toBe('0.00');
});

test('an active price list beats the general sale price', function () {
    $product = priceProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '5.00', 'is_active' => true]);
    PriceListItem::query()->create([
        'price_list_id' => $list->id,
        'product_id' => $product->id,
        'price' => '80.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);

    $quote = app(PriceResolver::class)->quote(priceDealer($list), $product, 1);

    expect($quote->source)->toBe(PriceSource::List)
        ->and($quote->net)->toBe('80.00')
        ->and($quote->documentDiscountPercent)->toBe('5.00');
});

test('a dealer special price beats the assigned list', function () {
    $product = priceProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '5.00', 'is_active' => true]);
    PriceListItem::query()->create([
        'price_list_id' => $list->id,
        'product_id' => $product->id,
        'price' => '80.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    $dealer = priceDealer($list);
    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $product->id,
        'price' => '70.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);

    $quote = app(PriceResolver::class)->quote($dealer->fresh('priceList'), $product, 1);

    expect($quote->source)->toBe(PriceSource::Dealer)
        ->and($quote->net)->toBe('70.00')
        ->and($quote->documentDiscountPercent)->toBe('5.00');
});

test('expired future and inactive prices are ignored', function () {
    $product = priceProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '5.00', 'is_active' => true]);
    $dealer = priceDealer($list);
    $today = now()->toDateString();

    foreach ([
        ['price' => '10.00', 'starts_at' => now()->subDays(10)->toDateString(), 'ends_at' => now()->subDay()->toDateString(), 'is_active' => true],
        ['price' => '11.00', 'starts_at' => now()->addDay()->toDateString(), 'ends_at' => null, 'is_active' => true],
        ['price' => '12.00', 'starts_at' => null, 'ends_at' => null, 'is_active' => false],
    ] as $row) {
        DealerPrice::query()->create([
            'dealer_id' => $dealer->id,
            'product_id' => $product->id,
            'minimum_quantity' => 1,
            'prices_include_vat' => false,
            'discount_percent' => '0.00',
            ...$row,
        ]);
    }

    PriceListItem::query()->create([
        'price_list_id' => $list->id,
        'product_id' => $product->id,
        'price' => '13.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => false,
    ]);

    $quote = app(PriceResolver::class)->quote($dealer->fresh('priceList'), $product, 1, now());

    expect($quote->source)->toBe(PriceSource::Product)
        ->and($quote->net)->toBe('100.00')
        ->and($today)->not->toBe('');
});

test('an inactive price list is ignored while the assignment stays', function () {
    $product = priceProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '5.00', 'is_active' => false]);
    PriceListItem::query()->create([
        'price_list_id' => $list->id,
        'product_id' => $product->id,
        'price' => '80.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    $dealer = priceDealer($list);

    $quote = app(PriceResolver::class)->quote($dealer->fresh('priceList'), $product, 1);

    expect($dealer->price_list_id)->toBe($list->id)
        ->and($quote->source)->toBe(PriceSource::Product)
        ->and($quote->documentDiscountPercent)->toBe('0.00');
});

test('the highest satisfied minimum quantity wins and a newer start breaks the tie', function () {
    $product = priceProduct();
    $dealer = priceDealer();

    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $product->id,
        'price' => '90.00',
        'minimum_quantity' => 1,
        'starts_at' => now()->subDays(3)->toDateString(),
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $product->id,
        'price' => '60.00',
        'minimum_quantity' => 10,
        'starts_at' => now()->subDays(2)->toDateString(),
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);
    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $product->id,
        'price' => '55.00',
        'minimum_quantity' => 10,
        'starts_at' => now()->subDay()->toDateString(),
        'prices_include_vat' => false,
        'discount_percent' => '0.00',
        'is_active' => true,
    ]);

    $resolver = app(PriceResolver::class);

    expect($resolver->quote($dealer, $product, 9)->net)->toBe('90.00')
        ->and($resolver->quote($dealer, $product, 10)->net)->toBe('55.00');
});

test('line discount is applied before vat and document discount stays off the unit price', function () {
    $product = priceProduct();
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '5.00', 'is_active' => true]);
    $dealer = priceDealer($list);
    DealerPrice::query()->create([
        'dealer_id' => $dealer->id,
        'product_id' => $product->id,
        'price' => '80.00',
        'minimum_quantity' => 1,
        'prices_include_vat' => false,
        'discount_percent' => '10.00',
        'is_active' => true,
    ]);

    $quote = app(PriceResolver::class)->quote($dealer->fresh('priceList'), $product, 1);

    expect($quote->discountedPrice)->toBe('72.00')
        ->and($quote->net)->toBe('72.00')
        ->and($quote->vat)->toBe('14.40')
        ->and($quote->gross)->toBe('86.40')
        ->and($quote->documentDiscountPercent)->toBe('5.00');
});

test('an administrator can create a price list', function () {
    $admin = actingAsRole(Role::Admin);

    $this->actingAs($admin)->post(route('price-lists.store'), [
        'name' => 'Okullar',
        'document_discount_percent' => '5',
    ])->assertRedirect();

    $this->assertDatabaseHas('price_lists', [
        'name' => 'Okullar',
        'document_discount_percent' => '5.00',
        'is_active' => true,
    ]);
});

test('dealers and warehouse users cannot open price lists', function () {
    $dealerUser = actingAsRole(Role::Dealer);
    $dealerUser->forceFill(['dealer_id' => Dealer::factory()->approved()->create()->id])->save();
    $warehouse = actingAsRole(Role::Warehouse);

    $this->actingAs($dealerUser)->get(route('price-lists.index'))->assertForbidden();
    $this->actingAs($warehouse)->get(route('price-lists.index'))->assertForbidden();
});

test('a public application cannot assign a price list', function () {
    $list = PriceList::query()->create(['name' => 'Okullar', 'document_discount_percent' => '0', 'is_active' => true]);

    $this->post(route('dealers.apply.store'), [
        'company_name' => 'Odunpazarı Kırtasiye',
        'contact_name' => 'Ayşe Yılmaz',
        'phone' => '02221234567',
        'email' => 'ayse@example.com',
        'tax_number' => '1234567890',
        'tax_office' => 'Odunpazarı',
        'address' => 'Atatürk Bulvarı No:1',
        'district' => 'odunpazari',
        'delivery_address' => 'Depo kapısı',
        'billing_address' => 'Atatürk Bulvarı No:1',
        'payment_term_days' => 30,
        'price_list_id' => $list->id,
    ])->assertRedirect(route('dealers.apply'));

    expect(Dealer::query()->where('email', 'ayse@example.com')->value('price_list_id'))->toBeNull();
});

test('a dealer sees the resolved sale price and not the general or purchase price', function () {
    $product = priceProduct('100.00');
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
    $dealer = priceDealer($list);
    $user = actingAsRole(Role::Dealer);
    $user->forceFill(['dealer_id' => $dealer->id])->save();

    $this->actingAs($user)->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Fiyat listesi')
        ->assertSee('72,00')
        ->assertSee('14,40')
        ->assertSee('86,40')
        ->assertDontSee('100,00')
        ->assertDontSee('40,00')
        ->assertDontSee(__('Purchase price'));
});
