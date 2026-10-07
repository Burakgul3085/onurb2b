<?php

use App\Enums\District;
use App\Enums\LedgerType;
use App\Enums\MailTemplateKey;
use App\Enums\OrderStatus;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Enums\Role;
use App\Enums\VatRate;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\LedgerEntry;
use App\Models\MailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReportExport;
use App\Models\StockLevel;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\Ops\DatabaseDumper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

test('the scheduler runs the backup at night and the notices in the morning', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('ops:backup')
        ->expectsOutputToContain('ops:cleanup')
        ->expectsOutputToContain('ops:notify-dues')
        ->expectsOutputToContain('ops:critical-stock')
        ->expectsOutputToContain('ops:daily-report')
        ->assertSuccessful();
});

test('a due date is mailed once and a later due date stays quiet', function () {
    $admin = actingAsRole(Role::Admin);
    $dealer = Dealer::factory()->approved()->create([
        'company_name' => 'Tepebaşı Okul Market',
        'email' => 'vade@example.test',
    ]);

    $soon = LedgerEntry::query()->create([
        'number' => 'CH-2026-00091',
        'dealer_id' => $dealer->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Sale,
        'debit' => '10.00',
        'credit' => '0.00',
        'document_date' => now()->toDateString(),
        'due_on' => now()->addDays(3)->toDateString(),
    ]);
    LedgerEntry::query()->create([
        'number' => 'CH-2026-00092',
        'dealer_id' => $dealer->id,
        'user_id' => $admin->id,
        'type' => LedgerType::Sale,
        'debit' => '10.00',
        'credit' => '0.00',
        'document_date' => now()->toDateString(),
        'due_on' => now()->addDays(20)->toDateString(),
    ]);

    $this->artisan('ops:notify-dues')->assertSuccessful();
    $this->artisan('ops:notify-dues')->assertSuccessful();

    expect(MailLog::query()->where('recipient', 'vade@example.test')->whereHas('template', function ($query) {
        $query->where('key', MailTemplateKey::DueDateApproaching);
    })->count())->toBe(1)
        ->and($soon->refresh()->due_notified_at)->not->toBeNull();
});

test('critical stock and yesterday sales reach only the matching staff', function () {
    $warehouse = actingAsRole(Role::Warehouse);
    $finance = actingAsRole(Role::FinanceOps);
    $dealerUser = actingAsRole(Role::Dealer);
    $dealer = Dealer::factory()->approved()->create();
    $dealerUser->update(['dealer_id' => $dealer->id]);

    $brand = Brand::query()->create(['name' => 'Onur', 'is_active' => true]);
    $category = Category::query()->create(['name' => 'Kalem', 'is_active' => true]);
    $unit = Unit::query()->create(['name' => 'Adet', 'multiplier' => 1, 'is_base' => true, 'is_active' => true]);
    $product = Product::query()->create([
        'sku' => 'KLM-001',
        'name' => 'Tükenmez Kalem',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'vat_rate' => VatRate::Twenty,
        'purchase_price' => '40.00',
        'sale_price' => '80.00',
        'prices_include_vat' => false,
        'minimum_stock' => 5,
        'critical_stock' => 5,
        'is_active' => true,
    ]);
    $store = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $store->id,
        'product_id' => $product->id,
        'physical_stock' => 2,
        'reserved_stock' => 0,
    ]);

    $this->artisan('ops:critical-stock')->assertSuccessful();

    expect(MailLog::query()->where('recipient', $warehouse->email)->where('body', 'like', '%KLM-001%')->exists())->toBeTrue()
        ->and(MailLog::query()->where('recipient', $dealerUser->email)->exists())->toBeFalse()
        ->and(MailLog::query()->where('recipient', $finance->email)->exists())->toBeFalse();

    LedgerEntry::query()->create([
        'number' => 'CH-2026-00093',
        'dealer_id' => $dealer->id,
        'user_id' => $finance->id,
        'type' => LedgerType::Sale,
        'debit' => '15.50',
        'credit' => '0.00',
        'document_date' => now()->subDay()->toDateString(),
        'due_on' => now()->addDays(20)->toDateString(),
    ]);
    $order = Order::query()->create([
        'number' => 'SP-2026-00021',
        'dealer_id' => $dealer->id,
        'user_id' => $dealerUser->id,
        'status' => OrderStatus::Pending,
        'province' => Dealer::PROVINCE,
        'district' => District::Tepebasi,
        'delivery_address' => 'Depo girişi',
        'document_discount_percent' => '0.00',
        'net' => '0.00',
        'vat' => '0.00',
        'gross' => '0.00',
        'discount_amount' => '0.00',
        'payable' => '0.00',
    ]);
    $order->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->save();

    $this->artisan('ops:daily-report')->assertSuccessful();

    $report = MailLog::query()->where('recipient', $finance->email)->first();

    expect($report)->not->toBeNull()
        ->and($report->body)->toContain('15,50')
        ->and($report->body)->toContain('Sipariş sayısı 1');
});

test('backups and old report files are kept for seven days', function () {
    $admin = actingAsRole(Role::Admin);
    $directory = storage_path('framework/testing/ops-backups');
    File::deleteDirectory($directory);
    config(['ops.backup_path' => $directory]);

    File::ensureDirectoryExists($directory);
    $old = $directory.DIRECTORY_SEPARATOR.'old.sql';
    File::put($old, 'old');
    touch($old, now()->subDays(8)->getTimestamp());

    $this->app->instance(DatabaseDumper::class, new class implements DatabaseDumper
    {
        public function dump(string $path): void
        {
            File::put($path, '-- backup');
        }
    });

    $this->artisan('ops:backup')->assertSuccessful();

    expect(File::exists($old))->toBeFalse()
        ->and(collect(File::files($directory))->contains(fn ($file) => str_starts_with($file->getFilename(), 'onurb2b-')))->toBeTrue();

    $path = 'reports/old-export.pdf';
    Storage::disk('local')->put($path, 'pdf');
    $export = ReportExport::query()->create([
        'user_id' => $admin->id,
        'type' => ReportType::Sales,
        'format' => ReportFormat::Pdf,
        'filters' => ['type' => ReportType::Sales->value],
        'status' => ReportExportStatus::Ready,
        'path' => $path,
    ]);
    $export->forceFill(['created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)])->save();

    $this->artisan('ops:cleanup')->assertSuccessful();

    expect(ReportExport::query()->whereKey($export->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($path))->toBeFalse();

    File::deleteDirectory($directory);
});
