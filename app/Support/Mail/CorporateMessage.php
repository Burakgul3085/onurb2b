<?php

namespace App\Support\Mail;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Mail;

class CorporateMessage
{
    public function send(string $recipient, string $subject, string $plainBody, string $eyebrow = 'Bildirim'): void
    {
        $html = $this->document($subject, $plainBody, $eyebrow);

        Mail::html($html, function ($message) use ($recipient, $subject, $plainBody) {
            $message->to($recipient)->subject($subject);
            $replyTo = config('mail.from.address');

            if (is_string($replyTo) && $replyTo !== '') {
                $message->replyTo($replyTo);
            }

            $message->getSymfonyMessage()->text($plainBody);
        });
    }

    public function document(string $subject, string $plainBody, string $eyebrow = 'Bildirim'): string
    {
        return view('mail.corporate', $this->data($subject, $plainBody, $eyebrow))->render();
    }

    public function card(string $subject, string $plainBody, string $eyebrow = 'Bildirim'): string
    {
        return view('mail.corporate-card', $this->data($subject, $plainBody, $eyebrow))->render();
    }

    /**
     * @return array{subjectLine: string, eyebrow: string, paragraphs: list<string>, actionUrl: ?string, legalName: string, address: string}
     */
    private function data(string $subject, string $plainBody, string $eyebrow): array
    {
        $company = CompanySetting::query()->first();
        [$paragraphs, $actionUrl] = $this->parts($plainBody);

        return [
            'subjectLine' => $subject,
            'eyebrow' => $eyebrow !== '' ? $eyebrow : 'Bildirim',
            'paragraphs' => $paragraphs,
            'actionUrl' => $actionUrl,
            'legalName' => $company?->legal_name ?: 'Onur Kırtasiye',
            'address' => $company?->address ?: 'Eskişehir',
        ];
    }

    /**
     * @return array{0: list<string>, 1: ?string}
     */
    private function parts(string $plain): array
    {
        $blocks = preg_split("/\r\n|\n|\r/", trim($plain)) ?: [];
        $paragraphs = [];
        $buffer = [];
        $actionUrl = null;

        $flush = function () use (&$buffer, &$paragraphs): void {
            $text = trim(implode("\n", $buffer));
            $buffer = [];

            if ($text !== '') {
                $paragraphs[] = $text;
            }
        };

        foreach ($blocks as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flush();

                continue;
            }

            if ($actionUrl === null && preg_match('#^https?://\S+$#', $trimmed) === 1) {
                $flush();
                $actionUrl = $trimmed;

                continue;
            }

            $buffer[] = $line;
        }

        $flush();

        if ($paragraphs === [] && $actionUrl === null && trim($plain) !== '') {
            $paragraphs[] = trim($plain);
        }

        return [$paragraphs, $actionUrl];
    }
}
