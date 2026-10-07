<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_return_id',
        'order_line_id',
        'pieces',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pieces' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => false);
    }

    /**
     * @return BelongsTo<OrderReturn, $this>
     */
    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    /**
     * @return BelongsTo<OrderLine, $this>
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }
}
