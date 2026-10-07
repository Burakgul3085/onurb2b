<?php

use App\Actions\Dealers\ApproveDealer;
use App\Actions\Finance\RecordCollection;
use App\Actions\Orders\CancelOrder;
use App\Actions\Prices\SavePriceRecord;
use App\Actions\Products\CreateProduct;
use App\Actions\Stock\RecordStockMovement;
use App\Actions\Users\UpdateUser;
use App\Enums\AuditAction;
use App\Enums\DealerApplicationStatus;
use App\Enums\District;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Enums\VatRate;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\PriceList;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;

test('login and logout are written once and a failed password is not', function () {
    $admin = actingAsRole(Role::Admin);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(AuditLog::query()->where('action', AuditAction::Login)->count())->toBe(0);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(AuditLog::query()->where('action', AuditAction::Login)->count())->toBe(1);

    $this->post('/logout')->assertRedirect('/');

    expect(AuditLog::query()->where('action', AuditAction::Logout)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::Login)->value('entity_number'))->toBe($admin->email);

    $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
});

test('staff can read the audit log and a dealer cannot see another firm', function () {
    $admin = actingAsRole(Role::Admin);
    $this->actingAs($admin);

    $brand = Brand::query()->create(['name' => 'Onur', 'is_active' => true]);
    $category = Category::query()->create(['name' => 'Kalem', 'is_active' => true]);
    $unit = Unit::query()->create(['name' => 'Adet', 'multiplier' => 1, 'is_base' => true, 'is_active' => true]);
    $product = app(CreateProduct::class)->execute([
        'sku' => 'KLM-001',
        'name' => 'Tükenmez Kalem',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'subcategory_id' => '',
        'unit_id' => $unit->id,
        'vat_rate' => VatRate::Twenty->value,
        'purchase_price' => '40',
        'sale_price' => '80',
        'prices_include_vat' => '0',
        'minimum_stock' => 1,
        'critical_stock' => 0,
        'description' => '',
        'barcodes' => '',
    ]);

    $list = PriceList::query()->create([
        'name' => 'Okullar',
        'document_discount_percent' => '0.00',
        'is_active' => true,
    ]);
    app(SavePriceRecord::class)->forList($list, [
        'product_id' => $product->id,
        'price' => '80',
        'minimum_quantity' => 1,
        'prices_include_vat' => '0',
        'discount_percent' => '10',
    ]);

    $warehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    app(RecordStockMovement::class)->adjust($warehouse, $product, 4, 'Sayım', $admin);

    $dealer = Dealer::factory()->approved()->create(['company_name' => 'Tepebaşı Okul Market']);
    $order = Order::query()->create([
        'number' => 'SP-2026-00008',
        'dealer_id' => $dealer->id,
        'user_id' => $admin->id,
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
    app(CancelOrder::class)->execute($order, $admin, 'Yanlış ürün');

    app(RecordCollection::class)->execute($dealer, '15.50', PaymentMethod::Transfer, '2026-10-07', 'Havale', $admin);

    $pending = Dealer::factory()->create([
        'company_name' => 'Odunpazarı Market',
        'email' => 'odun@example.test',
    ]);
    app(ApproveDealer::class)->execute($admin, $pending, 'Bayi-Gizli-2026');

    app(UpdateUser::class)->execute($admin, $admin, [
        'name' => $admin->name,
        'email' => $admin->email,
        'roles' => [Role::Admin->value, Role::Warehouse->value],
        'password' => 'Gizli-Parola-2026',
        'is_active' => true,
    ]);

    $stored = AuditLog::query()->get()->toJson();

    expect($stored)->not->toContain('Bayi-Gizli-2026')
        ->and($stored)->not->toContain('Gizli-Parola-2026')
        ->and(AuditLog::query()->where('action', AuditAction::ProductSaved)->where('entity_number', 'KLM-001')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::PriceSaved)->where('entity_number', 'KLM-001')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::StockAdjusted)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::OrderCancelled)->where('entity_number', 'SP-2026-00008')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::CollectionRecorded)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::DealerApproved)->where('entity_number', 'Odunpazarı Market')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::PermissionChanged)->where('entity_number', $admin->email)->exists())->toBeTrue()
        ->and(AuditLog::query()->first()->delete())->toBeFalse();

    $permission = AuditLog::query()->where('action', AuditAction::PermissionChanged)->where('entity_number', $admin->email)->latest('id')->first();

    expect($permission->old_values['roles'] ?? null)->toBe([Role::Admin->value])
        ->and($permission->new_values['roles'] ?? null)->toBe([Role::Admin->value, Role::Warehouse->value])
        ->and(collect(AuditLog::query()->pluck('new_values'))->contains(fn ($values) => is_array($values) && array_key_exists('password', $values)))->toBeFalse();

    $this->get(route('audit-logs.index'))
        ->assertOk()
        ->assertSee('SP-2026-00008')
        ->assertSee('Odunpazarı Market')
        ->assertSee('15,50')
        ->assertSee('Parola değişti')
        ->assertDontSee('Bayi-Gizli-2026')
        ->assertDontSee('Gizli-Parola-2026');

    $this->get(route('audit-logs.index', ['action' => AuditAction::OrderCancelled->value]))
        ->assertOk()
        ->assertSee('SP-2026-00008')
        ->assertDontSee('Odunpazarı Market');

    $linked = User::factory()->create(['dealer_id' => $dealer->id]);
    $linked->assignRole(Role::Admin->value);

    $this->actingAs($linked)->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs(actingAsRole(Role::Warehouse))->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs(actingAsRole(Role::FinanceOps))->get(route('audit-logs.index'))->assertForbidden();
    $this->actingAs(actingAsRole(Role::Dealer))->get(route('audit-logs.index'))->assertForbidden();

    expect($pending->refresh()->application_status)->toBe(DealerApplicationStatus::Approved);
});
