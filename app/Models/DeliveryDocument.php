<?php

namespace App\Models;

use App\Enums\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryDocument extends Model
{
    public const DISCLAIMER = 'Bu belge resmi e-İrsaliye/e-belge yerine geçmez.';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'delivery_id',
        'order_id',
        'dealer_id',
        'issued_on',
        'company_legal_name',
        'company_tax_number',
        'company_tax_office',
        'company_address',
        'company_footnote',
        'dealer_name',
        'dealer_tax_number',
        'dealer_tax_office',
        'order_number',
        'recipient_name',
        'driver_name',
        'province',
        'district',
        'delivery_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'district' => District::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => false);
    }

    public static function nextNumber(): string
    {
        $prefix = 'SB-'.now()->year.'-';
        $last = self::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->lockForUpdate()
            ->value('number');
        $sequence = $last === null ? 1 : ((int) substr((string) $last, -5)) + 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Dealer, $this>
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * @return HasMany<DeliveryDocumentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryDocumentLine::class);
    }
}
