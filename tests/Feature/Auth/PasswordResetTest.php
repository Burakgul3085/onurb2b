<?php

use App\Enums\MailTemplateKey;
use App\Models\Dealer;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

test('reset password link screen can be rendered', function () {
    $this->get('/forgot-password')->assertOk();
});

test('an active account receives a reset link and the log hides it', function () {
    $user = User::factory()->create(['email' => 'Ayse@Firma.test', 'name' => 'Ayse']);
    $sent = '';

    Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
        $sent = (string) $event->message->getTextBody();
    });

    $this->post('/forgot-password', ['email' => 'ayse@firma.test'])
        ->assertSessionHas('status', 'Bu adresin aktif bir hesabı varsa parola sıfırlama bağlantısı gönderildi.');

    expect($sent)->toContain('Ayse')
        ->and($sent)->toContain('/reset-password/');

    preg_match('#/reset-password/([^?\s]+)#', $sent, $match);
    $token = $match[1];
    $log = MailLog::query()->first();

    expect($log->recipient)->toBe('Ayse@Firma.test')
        ->and($log->subject)->toBe('Parola sıfırlama bağlantınız')
        ->and($log->body)->toContain('Bağlantı gizlendi.')
        ->and($log->body)->not->toContain($token)
        ->and($log->body)->not->toContain('reset-password');

    $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'yeni-parola',
        'password_confirmation' => 'yeni-parola',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

    expect(Hash::check('yeni-parola', $user->fresh()->password))->toBeTrue();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'baska-parola',
        'password_confirmation' => 'baska-parola',
    ])->assertSessionHasErrors('email');
});

test('a missing, inactive, or unapproved account gets the same notice and no mail', function () {
    $inactive = User::factory()->create(['email' => 'pasif@firma.test', 'is_active' => false]);
    $dealer = Dealer::factory()->create();
    $pending = User::factory()->create([
        'email' => 'bekleyen@firma.test',
        'dealer_id' => $dealer->id,
        'is_active' => true,
    ]);

    $this->post('/forgot-password', ['email' => 'yok@firma.test'])
        ->assertSessionHas('status', __('passwords.sent'));
    $this->post('/forgot-password', ['email' => $inactive->email])
        ->assertSessionHas('status', __('passwords.sent'));
    $this->post('/forgot-password', ['email' => $pending->email])
        ->assertSessionHas('status', __('passwords.sent'));

    expect(MailLog::query()->count())->toBe(0);

    MailTemplate::query()->where('key', MailTemplateKey::PasswordReset)->update(['is_active' => false]);
    $active = User::factory()->create();
    $this->post('/forgot-password', ['email' => $active->email])->assertSessionHas('status');

    expect(MailLog::query()->count())->toBe(0);
});
