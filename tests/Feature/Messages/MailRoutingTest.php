<?php

use App\Actions\Mail\ImportMailboxReplies;
use App\Enums\Role;
use App\Models\CompanySetting;
use App\Models\Dealer;
use App\Models\MailLog;
use App\Models\Message;
use App\Models\MessageThread;
use App\Support\Mail\InboundMail;
use App\Support\Mail\InboundMailParser;
use App\Support\Mail\MailboxReader;

test('an application copies the company mailbox as well as the applicant', function () {
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
    ])->assertRedirect();

    expect(MailLog::query()->where('recipient', 'ayse@example.com')->where('subject', 'Başvurunuz alındı')->exists())->toBeTrue()
        ->and(MailLog::query()->where('recipient', 'burakgul3085@gmail.com')->where('subject', 'Yeni bayi başvurusu: Odunpazarı Kırtasiye')->exists())->toBeTrue();
});

test('the system authority changes the company mailbox and a dealer changes only their own', function () {
    $super = actingAsRole(Role::SuperAdmin);
    $this->actingAs($super)->patch(route('settings.update'), [
        'legal_name' => 'Onur Kırtasiye',
        'address' => 'Eskişehir',
        'footnote' => 'Bu belge resmi e-İrsaliye/e-belge yerine geçmez.',
        'notification_email' => 'firma@onur.test',
    ])->assertRedirect();

    expect(CompanySetting::current()->notification_email)->toBe('firma@onur.test');

    $dealer = Dealer::factory()->approved()->create(['email' => 'mehmet@firma.test']);
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id, 'email' => 'mehmet@firma.test'])->save();
    $other = Dealer::factory()->approved()->create();
    $otherUser = actingAsRole(Role::Dealer);
    $otherUser->forceFill(['dealer_id' => $other->id])->save();

    $this->actingAs($shopper)->get(route('dealers.show', $dealer))->assertOk()->assertSee('Bildirim adresi');
    $this->actingAs($shopper)->patch(route('dealers.notification-email', $dealer), [
        'email' => 'yeni@firma.test',
    ])->assertRedirect();

    expect($dealer->refresh()->email)->toBe('yeni@firma.test')
        ->and($shopper->refresh()->email)->toBe('yeni@firma.test');

    $this->actingAs($otherUser)->patch(route('dealers.notification-email', $dealer), [
        'email' => 'baskasi@firma.test',
    ])->assertForbidden();
    $this->actingAs($shopper)->get(route('mail-templates.index'))->assertForbidden();
    $this->actingAs($shopper)->post(route('messages.import'))->assertForbidden();
});

test('a reply written to the mailbox is added to the matching thread once', function () {
    config(['mail.mailers.smtp.username' => 'burakgul3085@gmail.com']);

    $dealer = Dealer::factory()->approved()->create();
    $shopper = actingAsRole(Role::Dealer);
    $shopper->forceFill(['dealer_id' => $dealer->id])->save();
    $thread = MessageThread::query()->create([
        'dealer_id' => $dealer->id,
        'user_id' => $shopper->id,
        'subject' => 'Teslimat saati',
        'mail_token' => 'abc123def0',
    ]);
    $other = Dealer::factory()->approved()->create();
    $otherUser = actingAsRole(Role::Dealer);
    $otherUser->forceFill(['dealer_id' => $other->id])->save();

    $reader = new class implements MailboxReader
    {
        public array $seen = [];

        public function unseen(): array
        {
            return [
                new InboundMail('10', '<reply@mail>', 'mehmet@firma.test', 'Mehmet', 'Re: Yeni mesaj [OB-abc123def0]', "Sabah olur.\n"),
                new InboundMail('11', '<own@mail>', 'burakgul3085@gmail.com', 'Onur', 'Re: [OB-abc123def0]', 'Kendi kopyamız'),
                new InboundMail('12', '<other@mail>', 'diger@example.test', 'Diğer', 'Konu yok', 'Bağlantısız posta'),
            ];
        }

        public function markSeen(string $uid): void
        {
            $this->seen[] = $uid;
        }
    };

    $import = new ImportMailboxReplies($reader, new InboundMailParser);
    expect($import->execute())->toBe(1)
        ->and($import->execute())->toBe(0)
        ->and($reader->seen)->toBe(['10'])
        ->and(Message::query()->where('message_thread_id', $thread->id)->count())->toBe(1)
        ->and(Message::query()->first()->body)->toBe('Sabah olur.')
        ->and(Message::query()->first()->user_id)->toBeNull();

    $this->actingAs($otherUser)->get(route('messages.show', $thread))->assertNotFound();
    $this->actingAs($shopper)->get(route('messages.show', $thread))->assertOk()->assertSee('mehmet@firma.test')->assertSee('Sabah olur.');
});

test('the inbound parser keeps the plain text and the thread token', function () {
    $raw = "From: Mehmet <mehmet@firma.test>\r\nSubject: =?UTF-8?Q?Yan=C4=B1t?= [OB-abc123def0]\r\nMessage-ID: <abc@mail>\r\nContent-Type: multipart/alternative; boundary=sinir\r\n\r\n--sinir\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nSabah =C3=B6lur.\r\n--sinir\r\nContent-Type: text/html\r\n\r\n<p>html</p>\r\n--sinir--\r\n";
    $parser = new InboundMailParser;
    $mail = $parser->message('4', $raw);

    expect($mail)->not->toBeNull()
        ->and($mail->fromEmail)->toBe('mehmet@firma.test')
        ->and($mail->body)->toBe('Sabah ölur.')
        ->and($parser->token($mail->subject))->toBe('abc123def0');
});
