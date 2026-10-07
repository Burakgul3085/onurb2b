<?php

use App\Enums\District;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\LedgerEntry;
use App\Models\MessageThread;
use App\Models\Order;
use App\Models\Product;
use App\Models\Unit;
use Database\Seeders\UnitSeeder;

test('the dashboard shows staff sales and keeps a dealer on their own firm', function () {
    test()->seed(UnitSeeder::class);
    $brand = Brand::query()->create(['name' => 'Faber', 'is_active' => true]);
    $category = Category::query()->create(['name' => 'Kalem', 'is_active' => true]);
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
    $dealer = Dealer::factory()->approved()->create(['company_name' => 'Tepebaşı Okul Market']);
    $otherDealer = Dealer::factory()->approved()->create(['company_name' => 'Odunpazarı Market']);
    $admin = actingAsRole(Role::Admin);
    $warehouse = actingAsRole(Role::Warehouse);
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id])->save();
    $otherShopper = actingAsRole(Role::Dealer);
    $otherShopper->forceFill(['dealer_id' => $otherDealer->id])->save();
    $order = Order::query()->create([
        'number' => 'SP-2026-00008',
        'dealer_id' => $dealer->id,
        'user_id' => $shopper->id,
        'status' => OrderStatus::Delivered,
        'province' => Dealer::PROVINCE,
        'district' => District::Tepebasi,
        'delivery_address' => 'Depo girişi',
        'document_discount_percent' => '5.00',
        'net' => '205.20',
        'vat' => '41.04',
        'gross' => '246.24',
        'discount_amount' => '12.96',
        'payable' => '246.24',
    ]);
    $order->lines()->create([
        'product_id' => $product->id,
        'sku' => $product->sku,
        'product_name' => $product->name,
        'unit_name' => 'Adet',
        'quantity' => 3,
        'pieces_per_unit' => 1,
        'requested_pieces' => 3,
        'approved_pieces' => 3,
        'delivered_pieces' => 3,
        'returned_pieces' => 1,
        'unit_price' => '80.00',
        'discount_percent' => '10.00',
        'prices_include_vat' => false,
        'vat_rate' => '20',
        'net' => '205.20',
        'vat' => '41.04',
        'gross' => '246.24',
    ]);
    LedgerEntry::query()->create([
        'number' => 'CH-2026-00011',
        'dealer_id' => $dealer->id,
        'order_id' => $order->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Sale,
        'debit' => '246.24',
        'credit' => '0.00',
        'document_date' => now()->toDateString(),
        'due_on' => now()->toDateString(),
    ]);
    LedgerEntry::query()->create([
        'number' => 'CH-2026-00012',
        'dealer_id' => $dealer->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Collection,
        'method' => PaymentMethod::Transfer,
        'debit' => '0.00',
        'credit' => '100.00',
        'document_date' => now()->toDateString(),
    ]);
    LedgerEntry::query()->create([
        'number' => 'CH-2026-00013',
        'dealer_id' => $dealer->id,
        'order_id' => $order->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Return,
        'debit' => '0.00',
        'credit' => '82.08',
        'document_date' => now()->toDateString(),
    ]);
    MessageThread::query()->create([
        'dealer_id' => $dealer->id,
        'order_id' => $order->id,
        'user_id' => $shopper->id,
        'subject' => 'Teslimat saati',
    ]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Bugünkü satış')
        ->assertSee('246,24')
        ->assertSee('Tepebaşı Okul Market')
        ->assertSee('164,16')
        ->assertSee('KLM-001 — Tükenmez Kalem')
        ->assertDontSee('teslimat@onurb2b.test');

    $this->actingAs($admin)->get(route('dashboard', ['period' => 'last_month']))
        ->assertOk()
        ->assertSee('Bu dönemde kayıt yok.');

    $this->actingAs($warehouse)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('246,24')
        ->assertDontSee('Toplam cari alacak');

    $this->actingAs($shopper)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('64,16')
        ->assertSee('SP-2026-00008')
        ->assertSee('Teslimat saati')
        ->assertDontSee('Odunpazarı Market')
        ->assertDontSee('teslimat@onurb2b.test');

    $this->actingAs($otherShopper)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Tepebaşı Okul Market')
        ->assertDontSee('SP-2026-00008')
        ->assertDontSee('Teslimat saati')
        ->assertDontSee('246,24');
});
