<?php

namespace App\Models;

use App\Enums\AuditAction;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'entity_number',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entityName(): string
    {
        return match ($this->auditable_type) {
            Product::class => 'Ürün',
            PriceListItem::class, DealerPrice::class => 'Fiyat',
            Order::class => 'Sipariş',
            Dealer::class => 'Bayi',
            LedgerEntry::class => 'Cari',
            User::class => 'Kullanıcı',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    public function lines(?array $values): string
    {
        if ($values === null || $values === []) {
            return '';
        }

        $lines = [];

        foreach ($values as $key => $value) {
            $lines[] = $this->fieldLabel((string) $key).': '.$this->fieldValue((string) $key, $value);
        }

        return implode("\n", $lines);
    }

    private function fieldLabel(string $key): string
    {
        return match ($key) {
            'name' => 'Ad',
            'email' => 'E-posta',
            'is_active' => 'Aktif',
            'dealer_id' => 'Bayi',
            'roles' => 'Roller',
            'password_changed' => 'Parola değişti',
            'sku' => 'SKU',
            'sale_price' => 'Satış fiyatı',
            'purchase_price' => 'Alış fiyatı',
            'status' => 'Durum',
            'reason' => 'Gerekçe',
            'physical_stock' => 'Fiziki stok',
            'warehouse_id' => 'Depo',
            'quantity' => 'Adet',
            'product_id' => 'Ürün',
            'price' => 'Fiyat',
            'discount_percent' => 'İskonto',
            'application_status' => 'Başvuru',
            'type' => 'Tür',
            'debit' => 'Borç',
            'credit' => 'Alacak',
            'reverses' => 'Ters kayıt',
            default => $key,
        };
    }

    private function fieldValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Evet' : 'Hayır';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $item) => is_scalar($item) ? (string) $item : '', $value));
        }

        if (in_array($key, ['debit', 'credit', 'price', 'sale_price', 'purchase_price'], true)) {
            return Money::format((string) $value).' ₺';
        }

        return (string) $value;
    }
}
