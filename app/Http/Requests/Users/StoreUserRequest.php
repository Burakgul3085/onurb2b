<?php

namespace App\Http\Requests\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::enum(Role::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->assignsSuperAdminWithoutAuthority()) {
                $validator->errors()->add('roles', __('Only a system administrator can assign that role.'));
            }
        });
    }

    private function assignsSuperAdminWithoutAuthority(): bool
    {
        return in_array(Role::SuperAdmin->value, $this->input('roles', []), true)
            && ! $this->user()?->hasRole(Role::SuperAdmin->value);
    }
}
