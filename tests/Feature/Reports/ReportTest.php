<?php

use App\Enums\DeliveryStatus;
use App\Enums\District;
use App\Enums\LedgerType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReportExportStatus;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReportExport;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

test('staff can read sales reports and dealers cannot', function () {
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
        'critical_stock' => 5,
        'is_active' => true,
    ]);
    $otherProduct = Product::query()->create([
        'sku' => 'DEF-009',
        'name' => 'Defter',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'vat_rate' => '20',
        'purchase_price' => '10.00',
        'sale_price' => '20.00',
        'prices_include_vat' => false,
        'minimum_stock' => 1,
        'critical_stock' => 0,
        'is_active' => true,
    ]);
    $dealer = Dealer::factory()->approved()->create(['company_name' => 'Tepebaşı Okul Market']);
    $admin = actingAsRole(Role::Admin);
    $finance = actingAsRole(Role::FinanceOps);
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id])->save();
    $warehouseUser = actingAsRole(Role::Warehouse);
    $driver = actingAsRole(Role::Delivery);
    $driver->forceFill(['name' => 'Teslimat Personeli', 'email' => 'teslimat@onurb2b.test'])->save();
    $order = Order::query()->create([
        'number' => 'SP-2026-00001',
        'dealer_id' => $dealer->id,
        'user_id' => $admin->id,
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
        'number' => 'CH-2026-00001',
        'dealer_id' => $dealer->id,
        'order_id' => $order->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Sale,
        'debit' => '246.24',
        'credit' => '0.00',
        'document_date' => now()->toDateString(),
    ]);
    LedgerEntry::query()->create([
        'number' => 'CH-2026-00002',
        'dealer_id' => $dealer->id,
        'order_id' => $order->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Collection,
        'method' => PaymentMethod::Transfer,
        'debit' => '0.00',
        'credit' => '100.00',
        'document_date' => now()->toDateString(),
    ]);
    LedgerEntry::query()->create([
        'number' => 'CH-2026-00003',
        'dealer_id' => $dealer->id,
        'order_id' => $order->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Return,
        'debit' => '0.00',
        'credit' => '82.08',
        'document_date' => now()->toDateString(),
    ]);
    $low = Warehouse::query()->create(['name' => 'Kritik Depo', 'is_active' => true]);
    $full = Warehouse::query()->create(['name' => 'Dolu Depo', 'is_active' => true]);
    StockLevel::query()->create(['warehouse_id' => $low->id, 'product_id' => $product->id, 'physical_stock' => 2, 'reserved_stock' => 0]);
    StockLevel::query()->create(['warehouse_id' => $full->id, 'product_id' => $otherProduct->id, 'physical_stock' => 20, 'reserved_stock' => 0]);
    StockMovement::query()->create([
        'warehouse_id' => $low->id,
        'product_id' => $product->id,
        'type' => StockMovementType::Sale,
        'quantity' => 3,
        'physical_before' => 5,
        'physical_after' => 2,
        'user_id' => $admin->id,
    ]);
    Delivery::query()->create([
        'order_id' => $order->id,
        'dealer_id' => $dealer->id,
        'user_id' => $driver->id,
        'sequence' => 1,
        'scheduled_on' => now()->toDateString(),
        'status' => DeliveryStatus::Delivered,
        'province' => Dealer::PROVINCE,
        'district' => District::Tepebasi,
        'delivery_address' => 'Depo girişi',
        'recipient_name' => 'Ayşe',
    ]);

    $this->actingAs($shopper)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($warehouseUser)->get(route('reports.index'))->assertForbidden();

    $this->actingAs($finance)->get(route('reports.index', ['type' => 'sales', 'period' => 'all']))
        ->assertOk()
        ->assertSee('246,24')
        ->assertSee('Tepebaşı Okul Market');

    $this->actingAs($finance)->get(route('reports.index', ['type' => 'sales', 'period' => 'last_month']))
        ->assertOk()
        ->assertSee('Bu filtrede kayıt yok.');

    $this->actingAs($admin)->get(route('reports.index', ['type' => 'product_sales', 'period' => 'all']))
        ->assertOk()
        ->assertSee('KLM-001')
        ->assertSee('164,16');

    $this->actingAs($admin)->get(route('reports.index', ['type' => 'dealer_sales', 'period' => 'all']))
        ->assertOk()
        ->assertSee('82,08')
        ->assertSee('164,16');

    $this->actingAs($admin)->get(route('reports.index', ['type' => 'collections', 'period' => 'all']))
        ->assertOk()
        ->assertSee('100,00')
        ->assertSee('Havale');

    $this->actingAs($admin)->get(route('reports.index', ['type' => 'stock', 'status' => 'stock:critical']))
        ->assertOk()
        ->assertSee('Kritik Depo')
        ->assertDontSee('Dolu Depo');

    $this->actingAs($admin)->get(route('reports.index', ['type' => 'deliveries', 'period' => 'all']))
        ->assertOk()
        ->assertSee('Teslimat Personeli')
        ->assertDontSee('teslimat@onurb2b.test');

    $excel = $this->actingAs($admin)->get(route('reports.index', [
        'type' => 'product_sales',
        'period' => 'all',
        'format' => 'xlsx',
    ]));
    $excel->assertOk();
    $sheet = IOFactory::load($excel->baseResponse->getFile()->getPathname())->getActiveSheet();
    expect(collect($sheet->toArray())->flatten())->toContain('164,16');

    $pdf = $this->actingAs($admin)->get(route('reports.index', [
        'type' => 'sales',
        'period' => 'all',
        'format' => 'pdf',
    ]));
    $pdf->assertOk();
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF');

    config(['reports.queue_threshold' => 0]);
    $this->actingAs($admin)->get(route('reports.index', [
        'type' => 'sales',
        'period' => 'all',
        'format' => 'xlsx',
    ]))->assertRedirect();

    $export = ReportExport::query()->first();
    expect($export->status)->toBe(ReportExportStatus::Ready);

    $this->actingAs($admin)->get(route('reports.exports.show', $export))->assertOk();
    $this->actingAs($finance)->get(route('reports.exports.show', $export))->assertNotFound();
});
