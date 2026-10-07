<?php

use App\Enums\DealerApplicationStatus;
use App\Enums\District;
use App\Enums\Role;
use App\Models\Dealer;
use App\Models\User;

function dealerPayload(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Odunpazarı Kırtasiye',
        'contact_name' => 'Ayşe Yılmaz',
        'phone' => '02221234567',
        'email' => 'ayse@example.com',
        'tax_number' => '1234567890',
        'tax_office' => 'Odunpazarı',
        'address' => 'Atatürk Bulvarı No:1',
        'district' => District::Odunpazari->value,
        'delivery_address' => 'Depo kapısı',
        'billing_address' => 'Atatürk Bulvarı No:1',
        'payment_term_days' => 30,
        'notes' => 'Okul siparişleri',
    ], $overrides);
}

test('a visitor can submit a dealer application without receiving a login', function () {
    $this->post(route('dealers.apply.store'), dealerPayload([
        'email' => 'Ayse@Example.com',
    ]))->assertRedirect(route('dealers.apply'));

    $dealer = Dealer::query()->first();

    expect($dealer)->not->toBeNull()
        ->and($dealer->email)->toBe('ayse@example.com')
        ->and($dealer->province)->toBe(Dealer::PROVINCE)
        ->and($dealer->application_status)->toBe(DealerApplicationStatus::Pending)
        ->and($dealer->is_active)->toBeFalse()
        ->and(User::query()->count())->toBe(0);
});

test('a dealer application rejects an unknown district and a duplicate tax number', function () {
    Dealer::factory()->create(['tax_number' => '1234567890']);

    $this->post(route('dealers.apply.store'), dealerPayload([
        'district' => 'istanbul',
    ]))->assertSessionHasErrors('district');

    $this->post(route('dealers.apply.store'), dealerPayload([
        'email' => 'baska@example.com',
    ]))->assertSessionHasErrors('tax_number');
});

test('guests cannot open the dealer list', function () {
    $this->get(route('dealers.index'))->assertRedirect(route('login'));
});

test('warehouse staff cannot list dealers', function () {
    $this->actingAs(actingAsRole(Role::Warehouse))
        ->get(route('dealers.index'))
        ->assertForbidden();
});

test('finance can read dealers but cannot create or approve them', function () {
    $finance = actingAsRole(Role::FinanceOps);
    $dealer = Dealer::factory()->create();

    $this->actingAs($finance)->get(route('dealers.index'))->assertOk();
    $this->actingAs($finance)->get(route('dealers.show', $dealer))->assertOk();
    $this->actingAs($finance)->get(route('dealers.create'))->assertForbidden();
    $this->actingAs($finance)->post(route('dealers.approve', $dealer), [
        'password' => 'Bayi-Sifre-2026',
        'password_confirmation' => 'Bayi-Sifre-2026',
    ])->assertForbidden();
});

test('an administrator creates a pending inactive dealer', function () {
    $admin = actingAsRole(Role::Admin);

    $this->actingAs($admin)
        ->post(route('dealers.store'), dealerPayload())
        ->assertRedirect();

    $dealer = Dealer::query()->where('email', 'ayse@example.com')->first();

    expect($dealer->application_status)->toBe(DealerApplicationStatus::Pending)
        ->and($dealer->is_active)->toBeFalse()
        ->and($dealer->district)->toBe(District::Odunpazari);
});

test('an administrator cannot activate a dealer before approval', function () {
    $admin = actingAsRole(Role::Admin);
    $dealer = Dealer::factory()->create([
        'email' => 'ayse@example.com',
        'tax_number' => '1234567890',
    ]);

    $this->actingAs($admin)
        ->patch(route('dealers.update', $dealer), dealerPayload(['is_active' => '1']))
        ->assertSessionHasErrors('is_active');

    expect($dealer->fresh()->is_active)->toBeFalse();
});

test('approving a dealer opens one login linked to that firm', function () {
    $admin = actingAsRole(Role::Admin);
    $dealer = Dealer::factory()->create();

    $this->actingAs($admin)->post(route('dealers.approve', $dealer), [
        'password' => 'Bayi-Sifre-2026',
        'password_confirmation' => 'Bayi-Sifre-2026',
    ])->assertRedirect(route('dealers.show', $dealer));

    $dealer->refresh();
    $login = User::query()->where('email', $dealer->email)->first();

    expect($dealer->application_status)->toBe(DealerApplicationStatus::Approved)
        ->and($dealer->is_active)->toBeTrue()
        ->and($dealer->approved_by)->toBe($admin->id)
        ->and($login)->not->toBeNull()
        ->and($login->dealer_id)->toBe($dealer->id)
        ->and($login->hasRole(Role::Dealer->value))->toBeTrue();

    $this->post('/login', [
        'email' => $dealer->email,
        'password' => 'Bayi-Sifre-2026',
    ])->assertRedirect(route('dashboard'));
});

test('approval fails when the company email already belongs to a user', function () {
    $admin = actingAsRole(Role::Admin);
    $dealer = Dealer::factory()->create(['email' => 'dolu@example.com']);
    User::factory()->create(['email' => 'dolu@example.com']);

    $this->actingAs($admin)->post(route('dealers.approve', $dealer), [
        'password' => 'Bayi-Sifre-2026',
        'password_confirmation' => 'Bayi-Sifre-2026',
    ])->assertSessionHasErrors('email');

    expect($dealer->fresh()->application_status)->toBe(DealerApplicationStatus::Pending)
        ->and(User::query()->where('dealer_id', $dealer->id)->exists())->toBeFalse();
});

test('rejecting an application does not open a login', function () {
    $admin = actingAsRole(Role::Admin);
    $dealer = Dealer::factory()->create();

    $this->actingAs($admin)->post(route('dealers.reject', $dealer), [
        'rejection_reason' => 'Eksik vergi levhası',
    ])->assertRedirect(route('dealers.show', $dealer));

    expect($dealer->fresh()->application_status)->toBe(DealerApplicationStatus::Rejected)
        ->and($dealer->fresh()->is_active)->toBeFalse()
        ->and($dealer->fresh()->rejection_reason)->toBe('Eksik vergi levhası')
        ->and($dealer->users()->count())->toBe(0);

    $this->actingAs($admin)->post(route('dealers.approve', $dealer), [
        'password' => 'Bayi-Sifre-2026',
        'password_confirmation' => 'Bayi-Sifre-2026',
    ])->assertSessionHasErrors('application_status');
});

test('a dealer user can only see their own company', function () {
    actingAsRole(Role::Admin);
    $own = Dealer::factory()->approved()->create();
    $other = Dealer::factory()->approved()->create();
    $user = User::factory()->create(['dealer_id' => $own->id]);
    $user->assignRole(Role::Dealer->value);

    $this->actingAs($user)->get(route('dealers.index'))->assertForbidden();
    $this->actingAs($user)->get(route('dealers.show', $own))->assertOk();
    $this->actingAs($user)->get(route('dealers.show', $other))->assertForbidden();
    $this->actingAs($user)->patch(route('dealers.update', $own), [])->assertForbidden();
});

test('deactivating a dealer closes its login', function () {
    $admin = actingAsRole(Role::Admin);
    $dealer = Dealer::factory()->approved()->create([
        'email' => 'ayse@example.com',
        'tax_number' => '1234567890',
    ]);
    $user = User::factory()->create([
        'email' => 'giris@example.com',
        'dealer_id' => $dealer->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('dealers.update', $dealer), dealerPayload(['is_active' => '0']))
        ->assertRedirect(route('dealers.show', $dealer));

    expect($dealer->fresh()->is_active)->toBeFalse()
        ->and($user->fresh()->is_active)->toBeFalse();

    auth()->logout();

    $this->post('/login', [
        'email' => 'giris@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
