<?php

namespace App\Models;

use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'format',
        'filters',
        'status',
        'path',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'format' => ReportFormat::class,
            'filters' => 'array',
            'status' => ReportExportStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
