<?php

use App\Actions\Dealers\ApproveDealer;
use App\Actions\Mail\QueueTemplatedMail;
use App\Enums\District;
use App\Enums\MailStatus;
use App\Enums\MailTemplateKey;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Dealer;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Unit;
use App\Models\Warehouse;
use Database\Seeders\UnitSeeder;
use Illuminate\Support\Facades\Mail;

test('mail templates are stored and an inactive template is skipped', function () {
    expect(MailTemplate::query()->count())->toBe(18);
    expect(MailTemplate::query()->where('key', MailTemplateKey::DueDateApproaching)->first()->is_active)->toBeTrue();

    MailTemplate::query()->where('key', MailTemplateKey::OrderPlaced)->update(['is_active' => false]);

    app(QueueTemplatedMail::class)->execute(MailTemplateKey::OrderPlaced, 'bayi@example.test', [
        'contact' => 'Ali',
        'company' => 'Tepebaşı',
        'order_number' => 'SP-2026-00009',
    ]);

    expect(MailLog::query()->count())->toBe(0);
});

test('a failed send is kept on the mail log', function () {
    Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('smtp kapalı'));

    app(QueueTemplatedMail::class)->execute(MailTemplateKey::DealerApplication, 'bayi@example.test', [
        'contact' => 'Ali',
        'company' => 'Tepebaşı',
    ]);

    $log = MailLog::query()->first();

    expect($log->status)->toBe(MailStatus::Failed)
        ->and($log->error)->toContain('smtp kapalı')
        ->and($log->sent_at)->toBeNull()
        ->and($log->subject)->toBe('Başvurunuz alındı')
        ->and($log->delete())->toBeFalse();
});

test('dealer decisions and order messages notify the right people', function () {
    $admin = actingAsRole(Role::Admin);
    $super = actingAsRole(Role::SuperAdmin);
    $driver = actingAsRole(Role::Delivery);
    $warehouse = actingAsRole(Role::Warehouse);
    $driver->forceFill(['email' => 'teslimat@onurb2b.test'])->save();

    $pending = Dealer::factory()->create([
        'email' => 'basvuru@tepebasi-okul.test',
        'company_name' => 'Tepebaşı Okul Market',
        'contact_name' => 'Mehmet',
    ]);

    $this->actingAs($admin)->post(route('dealers.reject', $pending), [
        'rejection_reason' => 'Eksik vergi levhası',
    ]);

    $rejected = MailLog::query()->where('recipient', 'basvuru@tepebasi-okul.test')->first();
    expect($rejected->status)->toBe(MailStatus::Sent)
        ->and($rejected->body)->toContain('Eksik vergi levhası')
        ->and($rejected->template->key)->toBe(MailTemplateKey::DealerRejected);

    $approvedDealer = Dealer::factory()->create([
        'email' => 'onay@tepebasi-okul.test',
        'company_name' => 'Onay Market',
        'contact_name' => 'Ayşe',
    ]);
    app(ApproveDealer::class)->execute($admin, $approvedDealer, 'Bayi-Sifre-2026');

    expect(MailLog::query()->where('recipient', 'onay@tepebasi-okul.test')->pluck('mail_template_id'))
        ->toHaveCount(2);

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
    $dealer = Dealer::factory()->approved()->create([
        'email' => 'mehmet@tepebasi-okul.test',
        'company_name' => 'Sipariş Market',
    ]);
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id, 'name' => 'Mehmet'])->save();
    $other = actingAsRole(Role::Dealer);
    $other->forceFill(['dealer_id' => Dealer::factory()->approved()->create()->id])->save();
    $stockWarehouse = Warehouse::query()->create(['name' => 'Merkez Depo', 'is_active' => true]);
    StockLevel::query()->create([
        'warehouse_id' => $stockWarehouse->id,
        'product_id' => $product->id,
        'physical_stock' => 30,
        'reserved_stock' => 0,
    ]);

    $this->actingAs($shopper)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
    $this->actingAs($shopper)->post(route('orders.store'), [
        'delivery_address' => $dealer->delivery_address,
        'district' => District::Tepebasi->value,
    ]);

    $order = Order::query()->where('dealer_id', $dealer->id)->first();
    $placed = MailLog::query()->where('recipient', $shopper->email)->latest('id')->first();
    expect($placed->status)->toBe(MailStatus::Sent)
        ->and($placed->subject)->toBe('Siparişiniz alındı')
        ->and($placed->body)->toContain($order->number)
        ->and(MailLog::query()->where('recipient', $driver->email)->count())->toBe(0)
        ->and(MailLog::query()->where('recipient', 'burakgul3085@gmail.com')->where('subject', 'like', 'Yeni sipariş:%')->exists())->toBeTrue();

    $this->actingAs($admin)->post(route('orders.approve', $order), [
        'warehouse_id' => $stockWarehouse->id,
        'approved' => [$order->lines()->first()->id => 2],
    ]);
    $this->actingAs($admin)->post(route('orders.prepare', $order));
    $this->actingAs($admin)->post(route('finance.collections.store', $dealer), [
        'amount' => '100,00',
        'method' => PaymentMethod::Transfer->value,
        'document_date' => now()->toDateString(),
        'note' => 'Havale geldi',
    ]);

    expect(MailLog::query()->where('recipient', $shopper->email)->count())->toBe(3)
        ->and(MailLog::query()->where('recipient', $dealer->email)->where('subject', 'Tahsilatınız işlendi')->exists())->toBeTrue();

    $this->actingAs($warehouse)->get(route('messages.index'))->assertForbidden();
    $this->actingAs($shopper)->get(route('mail-logs.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('mail-templates.index'))->assertForbidden();

    $this->actingAs($shopper)->get(route('orders.show', $order))->assertOk()->assertSee('Mesaj yaz');
    $this->actingAs($shopper)->post(route('messages.store'), [
        'order_id' => $order->id,
        'subject' => 'Teslimat saati',
        'body' => 'Sabah gelsin',
    ])->assertRedirect();

    $thread = MessageThread::query()->where('dealer_id', $dealer->id)->first();
    expect($thread->order_id)->toBe($order->id);

    $notice = MailLog::query()->where('subject', 'like', 'Yeni mesaj: Teslimat saati [OB-%')->get();
    expect($notice)->toHaveCount(1)
        ->and($notice->first()->recipient)->toBe('burakgul3085@gmail.com')
        ->and($notice->pluck('recipient'))->not->toContain($driver->email);

    $this->actingAs($other)->get(route('messages.show', $thread))->assertNotFound();
    $this->actingAs($other)->post(route('messages.reply', $thread), ['body' => 'Girmem'])->assertNotFound();

    $this->actingAs($admin)->post(route('messages.reply', $thread), [
        'body' => 'Sabah 09:00',
    ])->assertRedirect();

    $this->actingAs($shopper)->get(route('messages.show', $thread))
        ->assertOk()
        ->assertSee('Sabah 09:00')
        ->assertDontSee($driver->email);

    expect(MailLog::query()->where('recipient', $shopper->email)->where('subject', 'like', 'Yeni mesaj: Teslimat saati [OB-%')->exists())->toBeTrue()
        ->and($thread->delete())->toBeFalse()
        ->and(Message::query()->first()->delete())->toBeFalse();

    $historical = $placed->subject;
    $template = MailTemplate::query()->where('key', MailTemplateKey::OrderPlaced)->first();
    $this->actingAs($super)->patch(route('mail-templates.update', $template), [
        'subject' => 'Konu değişti',
        'body' => 'Gövde değişti',
        'is_active' => '1',
    ])->assertRedirect();

    expect($placed->refresh()->subject)->toBe($historical)
        ->and($template->refresh()->subject)->toBe('Konu değişti');
});
