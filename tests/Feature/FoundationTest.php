<?php

test('health endpoint responds', function () {
    $this->get('/up')->assertOk();
});

test('responses include baseline security headers', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    $response->assertHeaderMissing('Strict-Transport-Security');
});

test('application locale and timezone are prepared for Turkey', function () {
    expect(config('app.locale'))->toBe('tr')
        ->and(config('app.fallback_locale'))->toBe('en')
        ->and(config('app.timezone'))->toBe('Europe/Istanbul');
});

test('login screen loads livewire', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('livewire.js', false);
});
