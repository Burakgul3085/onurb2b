<?php

namespace App\Models;

use App\Enums\LedgerType;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LedgerEntry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'dealer_id',
        'order_id',
        'delivery_id',
        'user_id',
        'type',
        'method',
        'debit',
        'credit',
        'document_date',
        'due_on',
        'note',
        'reverses_entry_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerType::class,
            'method' => PaymentMethod::class,
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'document_date' => 'date',
            'due_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => false);
    }

    public static function nextNumber(): string
    {
        $prefix = 'CH-'.now()->year.'-';
        $last = self::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->lockForUpdate()
            ->value('number');
        $sequence = $last === null ? 1 : ((int) substr((string) $last, -5)) + 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function canBeReversed(): bool
    {
        if ($this->type === LedgerType::Sale || $this->type === LedgerType::Reversal) {
            return false;
        }

        if ($this->relationLoaded('reversal')) {
            return $this->reversal === null;
        }

        return ! self::query()->where('reverses_entry_id', $this->id)->exists();
    }

    /**
     * @return BelongsTo<Dealer, $this>
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_entry_id');
    }

    /**
     * @return HasOne<LedgerEntry, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_entry_id');
    }

    /**
     * @return HasOne<OrderReturn, $this>
     */
    public function orderReturn(): HasOne
    {
        return $this->hasOne(OrderReturn::class);
    }
}
