<?php

namespace App\Data\Dealers;

use App\Enums\District;
use App\Models\Dealer;

readonly class DealerData
{
    public function __construct(
        public string $companyName,
        public string $contactName,
        public string $phone,
        public string $email,
        public string $taxNumber,
        public string $taxOffice,
        public District $district,
        public string $address,
        public string $deliveryAddress,
        public string $billingAddress,
        public int $paymentTermDays,
        public ?string $notes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $district = $data['district'];
        $notes = $data['notes'] ?? null;

        return new self(
            companyName: $data['company_name'],
            contactName: $data['contact_name'],
            phone: $data['phone'],
            email: $data['email'],
            taxNumber: $data['tax_number'],
            taxOffice: $data['tax_office'],
            district: $district instanceof District ? $district : District::from($district),
            address: $data['address'],
            deliveryAddress: $data['delivery_address'],
            billingAddress: $data['billing_address'],
            paymentTermDays: (int) $data['payment_term_days'],
            notes: is_string($notes) && trim($notes) !== '' ? trim($notes) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'phone' => $this->phone,
            'email' => $this->email,
            'tax_number' => $this->taxNumber,
            'tax_office' => $this->taxOffice,
            'province' => Dealer::PROVINCE,
            'district' => $this->district,
            'address' => $this->address,
            'delivery_address' => $this->deliveryAddress,
            'billing_address' => $this->billingAddress,
            'payment_term_days' => $this->paymentTermDays,
            'notes' => $this->notes,
        ];
    }
}
