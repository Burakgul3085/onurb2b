<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Dealer;
use App\Models\Delivery;
use App\Models\DeliveryDocument;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Unit;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;
use Illuminate\Http\UploadedFile;

function documentDelivery(int $delivered): ?DeliveryDocument
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
        'minimum_stock' => 1,
        'critical_stock' => 0,
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
    test()->actingAs($admin)->post(route('deliveries.dispatch', $delivery), [
        'shipped' => [$line->id => 3],
    ]);

    if ($delivered < 1) {
        test()->actingAs($admin)->post(route('deliveries.fail', $delivery), ['note' => 'Kapalı']);

        return null;
    }

    test()->actingAs($admin)->post(route('deliveries.complete', $delivery), [
        'recipient_name' => 'Ayşe',
        'delivered' => [$delivery->lines()->first()->id => $delivered],
    ])->assertRedirect();

    return DeliveryDocument::query()->first();
}

test('a completed delivery issues a note that keeps the company snapshot', function () {
    $document = documentDelivery(3);
    $document->load('lines');

    expect($document->number)->toBe('SB-'.now()->year.'-00001')
        ->and($document->company_legal_name)->toBe('Onur Kırtasiye')
        ->and($document->recipient_name)->toBe('Ayşe')
        ->and($document->lines)->toHaveCount(1)
        ->and($document->lines->first()->pieces)->toBe(3)
        ->and($document->delete())->toBeFalse();

    $html = view('documents.delivery', [
        'document' => $document,
        'logo' => null,
        'disclaimer' => DeliveryDocument::DISCLAIMER,
    ])->render();

    expect($html)->toContain(DeliveryDocument::DISCLAIMER)
        ->and($html)->toContain('Onur Kırtasiye')
        ->and($html)->toContain($document->order_number)
        ->and($html)->toContain('Ayşe')
        ->and($html)->toContain($document->driver_name);

    CompanySetting::current()->update([
        'legal_name' => 'Yeni Unvan',
        'footnote' => 'Ek dipnot',
    ]);

    $html = view('documents.delivery', [
        'document' => $document->refresh()->load('lines'),
        'logo' => null,
        'disclaimer' => DeliveryDocument::DISCLAIMER,
    ])->render();

    expect($html)->toContain('Onur Kırtasiye')
        ->and($html)->not->toContain('Yeni Unvan')
        ->and($html)->toContain(DeliveryDocument::DISCLAIMER);

    $admin = actingAsRole(Role::Admin);
    $response = test()->actingAs($admin)->get(route('documents.show', $document));

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');

    $dealer = actingAsRole(Role::Dealer);
    $dealer->forceFill(['dealer_id' => $document->dealer_id])->save();
    test()->actingAs($dealer)->get(route('documents.show', $document))->assertOk();

    $other = actingAsRole(Role::Dealer);
    $other->forceFill(['dealer_id' => Dealer::factory()->approved()->create()->id])->save();
    test()->actingAs($other)->get(route('documents.show', $document))->assertNotFound();
    auth()->logout();
    test()->get(route('documents.show', $document))->assertRedirect(route('login'));

    test()->actingAs($admin)->get(route('settings.edit'))->assertForbidden();
    $super = actingAsRole(Role::SuperAdmin);
    test()->actingAs($super)->patch(route('settings.update'), [
        'legal_name' => 'Onur Dağıtım',
        'address' => 'Eskişehir',
        'footnote' => 'Ek dipnot',
        'logo' => UploadedFile::fake()->create('logo.svg', 20, 'image/svg+xml'),
    ])->assertSessionHasErrors('logo');

    test()->actingAs($super)->patch(route('settings.update'), [
        'legal_name' => 'Onur Dağıtım',
        'tax_office' => 'Odunpazarı',
        'tax_number' => '1234567890',
        'address' => 'Eskişehir',
        'footnote' => 'Ek dipnot',
        'notification_email' => 'posta@onur.test',
    ])->assertRedirect();

    expect(CompanySetting::current()->legal_name)->toBe('Onur Dağıtım')
        ->and(CompanySetting::current()->notification_email)->toBe('posta@onur.test')
        ->and($document->refresh()->company_legal_name)->toBe('Onur Kırtasiye');
});

test('a failed delivery has no note and a partial note lists only what was received', function () {
    documentDelivery(0);

    expect(DeliveryDocument::query()->count())->toBe(0);
});

test('a partial note lists only the pieces that were received', function () {
    $document = documentDelivery(1);

    expect($document)->not->toBeNull()
        ->and($document->lines()->first()->pieces)->toBe(1)
        ->and($document->lines()->count())->toBe(1);
});
