<?php

namespace App\Http\Requests\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('user');

        return $subject instanceof User && ($this->user()?->can('update', $subject) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $subject = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($subject)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::enum(Role::class)],
            'is_active' => ['sometimes', 'boolean'],
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
