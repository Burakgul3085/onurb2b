<?php

namespace Database\Factories;

use App\Enums\DealerApplicationStatus;
use App\Enums\District;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dealer>
 */
class DealerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'phone' => '0222'.fake()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'tax_number' => fake()->unique()->numerify('##########'),
            'tax_office' => 'Eskişehir',
            'province' => Dealer::PROVINCE,
            'district' => District::Odunpazari,
            'address' => fake()->streetAddress(),
            'delivery_address' => fake()->streetAddress(),
            'billing_address' => fake()->streetAddress(),
            'payment_term_days' => 30,
            'notes' => null,
            'application_status' => DealerApplicationStatus::Pending,
            'rejection_reason' => null,
            'is_active' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'application_status' => DealerApplicationStatus::Approved,
            'is_active' => true,
            'approved_at' => now(),
        ]);
    }
}
