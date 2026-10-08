<?php

namespace App\Jobs;

use App\Enums\MailStatus;
use App\Models\MailLog;
use App\Support\Mail\CorporateMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class SendTemplatedMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $logId) {}

    public function handle(): void
    {
        $log = MailLog::query()->find($this->logId);

        if ($log === null || $log->status !== MailStatus::Queued) {
            return;
        }

        try {
            $log->loadMissing('template');
            app(CorporateMessage::class)->send(
                $log->recipient,
                $log->subject,
                $log->body,
                $log->template?->name ?? 'Bildirim',
            );

            $log->update([
                'status' => MailStatus::Sent,
                'sent_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => MailStatus::Failed,
                'error' => Str::limit($exception->getMessage(), 1000),
            ]);
        }
    }
}
