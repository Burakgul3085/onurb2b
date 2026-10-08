<?php

namespace App\Actions\Auth;

use App\Enums\MailStatus;
use App\Enums\MailTemplateKey;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendPasswordReset
{
    public function execute(User $user, string $token): void
    {
        $template = MailTemplate::query()
            ->where('key', MailTemplateKey::PasswordReset)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        $subject = Str::limit($this->fill($template->subject, [
            'contact' => $user->name,
        ], true), 255, '');
        $body = $this->fill($template->body, [
            'contact' => $user->name,
            'reset_url' => $url,
        ], false);
        $hidden = 'Bağlantı gizlendi.';

        $log = MailLog::query()->create([
            'mail_template_id' => $template->id,
            'recipient' => $user->email,
            'subject' => str_replace($url, $hidden, $subject),
            'body' => str_replace($url, $hidden, $body),
            'status' => MailStatus::Queued,
        ]);

        try {
            Mail::raw($body, function ($message) use ($user, $subject) {
                $message->to($user->email)->subject($subject);
                $replyTo = config('mail.from.address');

                if (is_string($replyTo) && $replyTo !== '') {
                    $message->replyTo($replyTo);
                }
            });

            $log->update([
                'status' => MailStatus::Sent,
                'sent_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => MailStatus::Failed,
                'error' => Str::limit(str_replace($url, $hidden, $exception->getMessage()), 1000, ''),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function fill(string $template, array $replacements, bool $singleLine): string
    {
        $search = [];
        $replace = [];

        foreach ($replacements as $key => $value) {
            $text = $value;

            if ($singleLine) {
                $text = trim((string) preg_replace('/\s+/', ' ', $text));
            }

            $search[] = '{'.$key.'}';
            $replace[] = $text;
        }

        return str_replace($search, $replace, $template);
    }
}
