<?php

use App\Enums\Role;
use App\Enums\VatRate;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\UnitSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function catalogFixture(): array
{
    test()->seed(UnitSeeder::class);

    $brand = Brand::query()->create(['name' => 'Faber', 'is_active' => true]);
    $category = Category::query()->create(['name' => 'Kalem', 'is_active' => true]);
    $subcategory = Category::query()->create([
        'name' => 'Tükenmez',
        'parent_id' => $category->id,
        'is_active' => true,
    ]);

    return [
        'brand' => $brand,
        'category' => $category,
        'subcategory' => $subcategory,
        'unit' => Unit::query()->where('name', 'Adet')->first(),
    ];
}

function productPayload(array $fixture, array $overrides = []): array
{
    return array_merge([
        'sku' => 'KAL-001',
        'name' => 'Mavi tükenmez',
        'brand_id' => $fixture['brand']->id,
        'category_id' => $fixture['category']->id,
        'subcategory_id' => $fixture['subcategory']->id,
        'unit_id' => $fixture['unit']->id,
        'vat_rate' => VatRate::Twenty->value,
        'purchase_price' => '10,50',
        'sale_price' => '25,00',
        'prices_include_vat' => '0',
        'minimum_stock' => 20,
        'critical_stock' => 5,
        'description' => 'Okul kalemi',
        'barcodes' => "8690000000011\n8690000000028",
    ], $overrides);
}

test('a case converts to 200 pieces', function () {
    $this->seed(UnitSeeder::class);

    expect(Unit::query()->where('name', 'Paket')->first()->pieces())->toBe(10)
        ->and(Unit::query()->where('name', 'Koli')->first()->pieces())->toBe(200);
});

test('guests cannot open the product list', function () {
    $this->get(route('products.index'))->assertRedirect(route('login'));
});

test('an administrator can save a product with two barcodes', function () {
    Storage::fake('public');
    $admin = actingAsRole(Role::Admin);
    $fixture = catalogFixture();

    $this->actingAs($admin)->post(route('products.store'), [
        ...productPayload($fixture),
        'image' => UploadedFile::fake()->image('kalem.jpg'),
    ])->assertRedirect();

    $product = Product::query()->where('sku', 'KAL-001')->first();

    expect($product)->not->toBeNull()
        ->and($product->purchase_price)->toBe('10.50')
        ->and($product->sale_price)->toBe('25.00')
        ->and($product->category_id)->toBe($fixture['subcategory']->id)
        ->and($product->barcodes)->toHaveCount(2)
        ->and($product->image_path)->not->toBeNull()
        ->and($product->saleBreakdown()['gross'])->toBe('30.00');

    Storage::disk('public')->assertExists($product->image_path);
});

test('a product rejects a duplicate barcode and a critical stock above the minimum', function () {
    $admin = actingAsRole(Role::Admin);
    $fixture = catalogFixture();
    $this->actingAs($admin)->post(route('products.store'), productPayload($fixture))->assertRedirect();

    $this->actingAs($admin)->post(route('products.store'), productPayload($fixture, [
        'sku' => 'KAL-002',
        'barcodes' => '8690000000011',
    ]))->assertSessionHasErrors('barcodes');

    $this->actingAs($admin)->post(route('products.store'), productPayload($fixture, [
        'sku' => 'KAL-003',
        'barcodes' => '',
        'minimum_stock' => 1,
        'critical_stock' => 5,
    ]))->assertSessionHasErrors('critical_stock');
});

test('a subcategory cannot be nested under another subcategory', function () {
    $admin = actingAsRole(Role::Admin);
    $fixture = catalogFixture();

    $this->actingAs($admin)->post(route('categories.store'), [
        'name' => 'Mavi',
        'parent_id' => $fixture['subcategory']->id,
    ])->assertSessionHasErrors('parent_id');
});

test('the base unit cannot be deactivated', function () {
    $admin = actingAsRole(Role::Admin);
    $this->seed(UnitSeeder::class);
    $piece = Unit::query()->where('is_base', true)->first();

    $this->actingAs($admin)->patch(route('units.update', $piece), [
        'name' => 'Adet',
        'is_active' => '0',
    ])->assertSessionHasErrors('is_active');

    expect($piece->fresh()->is_active)->toBeTrue();
});

test('warehouse staff can read products but cannot manage the catalog', function () {
    $warehouse = actingAsRole(Role::Warehouse);
    $this->seed(UnitSeeder::class);

    $this->actingAs($warehouse)->get(route('products.index'))->assertOk();
    $this->actingAs($warehouse)->get(route('products.create'))->assertForbidden();
    $this->actingAs($warehouse)->get(route('brands.index'))->assertForbidden();
});

test('a dealer sees active sale prices and not another firm cost or an inactive product', function () {
    actingAsRole(Role::Admin);
    $fixture = catalogFixture();
    $visible = Product::query()->create([
        'sku' => 'GOR-1',
        'name' => 'Görünen kalem',
        'brand_id' => $fixture['brand']->id,
        'category_id' => $fixture['category']->id,
        'unit_id' => $fixture['unit']->id,
        'vat_rate' => VatRate::Twenty,
        'purchase_price' => '10.50',
        'sale_price' => '25.00',
        'prices_include_vat' => false,
        'minimum_stock' => 1,
        'critical_stock' => 1,
        'is_active' => true,
    ]);
    $hidden = Product::query()->create([
        'sku' => 'GIZ-1',
        'name' => 'Gizli kalem',
        'brand_id' => $fixture['brand']->id,
        'category_id' => $fixture['category']->id,
        'unit_id' => $fixture['unit']->id,
        'vat_rate' => VatRate::Twenty,
        'purchase_price' => '10.50',
        'sale_price' => '25.00',
        'prices_include_vat' => false,
        'minimum_stock' => 1,
        'critical_stock' => 1,
        'is_active' => false,
    ]);

    $dealer = Dealer::factory()->approved()->create();
    $user = User::factory()->create(['dealer_id' => $dealer->id]);
    $user->assignRole(Role::Dealer->value);

    $this->actingAs($user)->get(route('products.index'))
        ->assertOk()
        ->assertSee('Görünen kalem')
        ->assertSee('25,00')
        ->assertDontSee('10,50')
        ->assertDontSee('Gizli kalem');

    $this->actingAs($user)->get(route('products.show', $hidden))->assertForbidden();
    $this->actingAs($user)->get(route('products.show', $visible))->assertOk()->assertDontSee('10,50');
});
