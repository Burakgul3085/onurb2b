<?php

namespace App\Enums;

enum ReportExportStatus: string
{
    case Queued = 'queued';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Kuyrukta',
            self::Ready => 'Hazır',
            self::Failed => 'Başarısız',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Ready => 'on',
            self::Failed => 'off',
            default => 'wait',
        };
    }
}
