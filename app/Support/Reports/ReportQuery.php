<?php

namespace App\Support\Reports;

use App\Enums\ReportPeriod;
use App\Enums\ReportType;

final class ReportQuery
{
    public function __construct(
        public ?ReportType $type,
        public ReportPeriod $period,
        public ?string $from,
        public ?string $to,
        public ?int $dealerId,
        public ?int $productId,
        public ?int $categoryId,
        public ?int $brandId,
        public ?string $status,
    ) {}

    public function start(): ?string
    {
        return $this->period->bounds($this->from, $this->to)[0];
    }

    public function end(): ?string
    {
        return $this->period->bounds($this->from, $this->to)[1];
    }

    public function statusValue(string $prefix): ?string
    {
        $marker = $prefix.':';

        if ($this->status === null || ! str_starts_with($this->status, $marker)) {
            return null;
        }

        $value = substr($this->status, strlen($marker));

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: isset($data['type']) ? ReportType::from((string) $data['type']) : null,
            period: ReportPeriod::tryFrom((string) ($data['period'] ?? '')) ?? ReportPeriod::ThisMonth,
            from: self::text($data['from'] ?? null),
            to: self::text($data['to'] ?? null),
            dealerId: self::id($data['dealer_id'] ?? null),
            productId: self::id($data['product_id'] ?? null),
            categoryId: self::id($data['category_id'] ?? null),
            brandId: self::id($data['brand_id'] ?? null),
            status: self::text($data['status'] ?? null),
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function parameters(): array
    {
        return array_filter([
            'type' => $this->type?->value,
            'period' => $this->period->value,
            'from' => $this->from,
            'to' => $this->to,
            'dealer_id' => $this->dealerId,
            'product_id' => $this->productId,
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'status' => $this->status,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private static function id(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
