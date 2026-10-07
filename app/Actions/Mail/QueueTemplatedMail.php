<?php

namespace App\Actions\Mail;

use App\Enums\MailStatus;
use App\Enums\MailTemplateKey;
use App\Jobs\SendTemplatedMail;
use App\Models\MailLog;
use App\Models\MailTemplate;
use Illuminate\Support\Str;

class QueueTemplatedMail
{
    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    public function execute(MailTemplateKey $key, string $recipient, array $replacements): bool
    {
        $recipient = trim($recipient);

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $template = MailTemplate::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return false;
        }

        $log = MailLog::query()->create([
            'mail_template_id' => $template->id,
            'recipient' => $recipient,
            'subject' => Str::limit($this->fill($template->subject, $replacements, true), 255, ''),
            'body' => $this->fill($template->body, $replacements, false),
            'status' => MailStatus::Queued,
        ]);

        SendTemplatedMail::dispatch($log->id);

        return true;
    }

    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    private function fill(string $template, array $replacements, bool $singleLine): string
    {
        $search = [];
        $replace = [];

        foreach ($replacements as $key => $value) {
            $text = (string) $value;

            if ($singleLine) {
                $text = trim((string) preg_replace('/\s+/', ' ', $text));
            }

            $search[] = '{'.$key.'}';
            $replace[] = $text;
        }

        return str_replace($search, $replace, $template);
    }
}
