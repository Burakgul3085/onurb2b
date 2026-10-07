@php
    $selectedRoles = old('roles', $subject ? $subject->roles->pluck('name')->all() : []);
    $activeValue = old('is_active', ($subject?->is_active ?? true) ? '1' : '0');
    $isActive = $activeValue === true || $activeValue === 1 || $activeValue === '1';
@endphp

<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $subject?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $subject?->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div>
    <x-input-label for="password" :value="__('Password')" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
    <x-input-error class="mt-2" :messages="$errors->get('password')" />
</div>

<div>
    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
</div>

<fieldset>
    <legend class="form-section">{{ __('Roles') }}</legend>
    <div class="mt-2 space-y-2">
        @foreach ($roles as $role)
            <label class="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" name="roles[]" value="{{ $role->value }}" @checked(in_array($role->value, $selectedRoles, true))>
                {{ $role->label() }}
            </label>
        @endforeach
    </div>
    <x-input-error class="mt-2" :messages="$errors->get('roles')" />
    <x-input-error class="mt-2" :messages="$errors->get('roles.*')" />
</fieldset>

@if ($canChangeStatus)
    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            {{ __('Active') }}
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
@elseif ($subject)
    <p class="text-sm text-ink-muted">{{ __('Status') }}: {{ $subject->is_active ? __('Active') : __('Inactive') }}</p>
@endif
