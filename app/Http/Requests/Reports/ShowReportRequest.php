<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportFormat;
use App\Enums\ReportPeriod;
use App\Enums\ReportType;
use App\Policies\ReportPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app(ReportPolicy::class)->viewAny($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ReportType::class)],
            'period' => ['nullable', Rule::enum(ReportPeriod::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'dealer_id' => ['nullable', 'integer', 'exists:dealers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'format' => ['nullable', Rule::enum(ReportFormat::class)],
        ];
    }
}
