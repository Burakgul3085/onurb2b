<?php

namespace App\Jobs;

use App\Enums\MailStatus;
use App\Models\MailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
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
            Mail::raw($log->body, function ($message) use ($log) {
                $message->to($log->recipient)->subject($log->subject);
            });

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
