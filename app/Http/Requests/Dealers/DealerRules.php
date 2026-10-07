<?php

namespace App\Http\Requests\Dealers;

use App\Enums\District;
use Illuminate\Validation\Rule;

class DealerRules
{
    /**
     * @return array<string, mixed>
     */
    public static function fields(?int $ignoreDealerId = null): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('dealers', 'email')->ignore($ignoreDealerId),
            ],
            'tax_number' => [
                'required',
                'string',
                'regex:/^\d{10,11}$/',
                Rule::unique('dealers', 'tax_number')->ignore($ignoreDealerId),
            ],
            'tax_office' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'district' => ['required', Rule::enum(District::class)],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'billing_address' => ['required', 'string', 'max:1000'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'company_name' => 'firma adı',
            'contact_name' => 'yetkili',
            'phone' => 'telefon',
            'email' => 'e-posta',
            'tax_number' => 'vergi numarası',
            'tax_office' => 'vergi dairesi',
            'address' => 'adres',
            'district' => 'ilçe',
            'delivery_address' => 'teslimat adresi',
            'billing_address' => 'fatura adresi',
            'payment_term_days' => 'ödeme vadesi',
            'notes' => 'not',
            'password' => 'parola',
            'rejection_reason' => 'ret nedeni',
            'is_active' => 'durum',
        ];
    }
}
