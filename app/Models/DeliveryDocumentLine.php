<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDocumentLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_document_id',
        'sku',
        'product_name',
        'unit_name',
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
     * @return BelongsTo<DeliveryDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(DeliveryDocument::class, 'delivery_document_id');
    }
}
