<?php

namespace App\Enums;

enum MailStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Kuyrukta',
            self::Sent => 'Gönderildi',
            self::Failed => 'Başarısız',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Sent => 'on',
            self::Failed => 'off',
            default => 'wait',
        };
    }
}
