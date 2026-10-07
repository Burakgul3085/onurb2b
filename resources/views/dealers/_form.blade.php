@php
    $selectedDistrict = old('district', $dealer?->district?->value);
@endphp

<div>
    <x-input-label for="company_name" :value="__('Company name')" />
    <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $dealer?->company_name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
</div>

<div>
    <x-input-label for="contact_name" :value="__('Contact name')" />
    <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full" :value="old('contact_name', $dealer?->contact_name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('contact_name')" />
</div>

<div>
    <x-input-label for="phone" :value="__('Phone')" />
    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $dealer?->phone)" required />
    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
</div>

<div>
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $dealer?->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div>
    <x-input-label for="tax_number" :value="__('Tax number')" />
    <x-text-input id="tax_number" name="tax_number" type="text" class="mt-1 block w-full" :value="old('tax_number', $dealer?->tax_number)" required />
    <x-input-error class="mt-2" :messages="$errors->get('tax_number')" />
</div>

<div>
    <x-input-label for="tax_office" :value="__('Tax office')" />
    <x-text-input id="tax_office" name="tax_office" type="text" class="mt-1 block w-full" :value="old('tax_office', $dealer?->tax_office)" required />
    <x-input-error class="mt-2" :messages="$errors->get('tax_office')" />
</div>

<div>
    <x-input-label for="district" :value="__('District')" />
    <p class="mt-1 text-sm text-ink-muted">{{ __('Province') }}: {{ \App\Models\Dealer::PROVINCE }}</p>
    <select id="district" name="district" class="field mt-1" required>
        <option value="">{{ __('Select a district') }}</option>
        @foreach ($districts as $district)
            <option value="{{ $district->value }}" @selected($selectedDistrict === $district->value)>{{ $district->label() }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('district')" />
</div>

<div>
    <x-input-label for="address" :value="__('Address')" />
    <textarea id="address" name="address" rows="2" class="field mt-1" required>{{ old('address', $dealer?->address) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('address')" />
</div>

<div>
    <x-input-label for="delivery_address" :value="__('Delivery address')" />
    <textarea id="delivery_address" name="delivery_address" rows="2" class="field mt-1" required>{{ old('delivery_address', $dealer?->delivery_address) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('delivery_address')" />
</div>

<div>
    <x-input-label for="billing_address" :value="__('Billing address')" />
    <textarea id="billing_address" name="billing_address" rows="2" class="field mt-1" required>{{ old('billing_address', $dealer?->billing_address) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('billing_address')" />
</div>

<div>
    <x-input-label for="payment_term_days" :value="__('Payment term (days)')" />
    <x-text-input id="payment_term_days" name="payment_term_days" type="number" min="0" max="3650" class="mt-1 block w-full" :value="old('payment_term_days', $dealer?->payment_term_days ?? 0)" required />
    <x-input-error class="mt-2" :messages="$errors->get('payment_term_days')" />
</div>

@isset($priceLists)
    <div>
        <x-input-label for="price_list_id" :value="__('Price list')" />
        <select id="price_list_id" name="price_list_id" class="field mt-1">
            <option value="">{{ __('No price list') }}</option>
            @foreach ($priceLists as $priceList)
                <option value="{{ $priceList->id }}" @selected((string) old('price_list_id', $dealer?->price_list_id) === (string) $priceList->id)>{{ $priceList->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('price_list_id')" />
    </div>
@endisset

<div>
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" rows="3" class="field mt-1">{{ old('notes', $dealer?->notes) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
</div>

@if ($dealer && $dealer->application_status === \App\Enums\DealerApplicationStatus::Approved)
    @php
        $activeValue = old('is_active', $dealer->is_active ? '1' : '0');
        $isActive = $activeValue === true || $activeValue === 1 || $activeValue === '1';
    @endphp
    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            {{ __('Active') }}
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
@endif
