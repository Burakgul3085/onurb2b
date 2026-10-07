<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'dealer_id',
        'user_id',
        'sequence',
        'scheduled_on',
        'scheduled_time',
        'status',
        'province',
        'district',
        'delivery_address',
        'recipient_name',
        'note',
        'proof_path',
        'departed_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'scheduled_on' => 'date',
            'status' => DeliveryStatus::class,
            'district' => District::class,
            'departed_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Delivery $delivery) {
            return $delivery->status === DeliveryStatus::Preparing;
        });
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
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<DeliveryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class);
    }

    /**
     * @return HasOne<DeliveryDocument, $this>
     */
    public function document(): HasOne
    {
        return $this->hasOne(DeliveryDocument::class);
    }
}
