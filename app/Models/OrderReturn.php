<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'dealer_id',
        'warehouse_id',
        'user_id',
        'ledger_entry_id',
        'note',
    ];

    protected static function booted(): void
    {
        static::deleting(fn () => false);
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
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
    public function entry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    /**
     * @return HasMany<OrderReturnLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderReturnLine::class);
    }
}
