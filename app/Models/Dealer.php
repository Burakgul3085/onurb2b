<?php

namespace App\Models;

use App\Enums\DealerApplicationStatus;
use App\Enums\District;
use Database\Factories\DealerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    /** @use HasFactory<DealerFactory> */
    use HasFactory;

    public const PROVINCE = 'Eskişehir';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'email',
        'tax_number',
        'tax_office',
        'province',
        'district',
        'address',
        'delivery_address',
        'billing_address',
        'payment_term_days',
        'notes',
        'application_status',
        'rejection_reason',
        'is_active',
        'approved_at',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'district' => District::class,
            'application_status' => DealerApplicationStatus::class,
            'payment_term_days' => 'integer',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
