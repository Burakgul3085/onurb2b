<?php

namespace App\Support\Mail;

class InboundMailParser
{
    public function message(string $uid, string $raw): ?InboundMail
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $parts = explode("\n\n", $raw, 2);
        $headerText = $parts[0] ?? '';
        $body = $parts[1] ?? '';
        $headers = $this->headers($headerText);
        $from = $this->address($headers['from'] ?? '');
        $subject = $this->decodeHeader($headers['subject'] ?? '');
        $messageId = trim($headers['message-id'] ?? '', " <>\t");

        if ($from['email'] === '' || $messageId === '') {
            return null;
        }

        $text = $this->text($headers, $body);

        if (trim($text) === '') {
            return null;
        }

        return new InboundMail($uid, $messageId, $from['email'], $from['name'], $subject, trim($text));
    }

    public function token(string $subject): ?string
    {
        if (preg_match('/\[OB-([a-z0-9]{6,16})\]/i', $subject, $match) !== 1) {
            return null;
        }

        return strtolower($match[1]);
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $headerText): array
    {
        $unfolded = preg_replace("/\n[ \t]+/", ' ', $headerText) ?? $headerText;
        $headers = [];

        foreach (explode("\n", $unfolded) as $line) {
            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $match) !== 1) {
                continue;
            }

            $headers[strtolower($match[1])] = trim($match[2]);
        }

        return $headers;
    }

    /**
     * @return array{name: string, email: string}
     */
    private function address(string $from): array
    {
        if (preg_match('/<([^>]+)>/', $from, $match) === 1) {
            $name = trim(str_replace($match[0], '', $from), " \t\"");

            return ['name' => $this->decodeHeader($name), 'email' => strtolower(trim($match[1]))];
        }

        return ['name' => '', 'email' => strtolower(trim($from))];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function text(array $headers, string $body): string
    {
        $type = strtolower($headers['content-type'] ?? 'text/plain');

        if (str_contains($type, 'multipart/') && preg_match('/boundary="?([^";]+)"?/i', $type, $match) === 1) {
            $boundary = preg_quote($match[1], '/');
            $chunks = preg_split('/--'.$boundary.'/', $body) ?: [];

            foreach ($chunks as $chunk) {
                $chunk = ltrim($chunk, "\n");

                if ($chunk === '' || str_starts_with($chunk, '--')) {
                    continue;
                }

                $piece = explode("\n\n", $chunk, 2);
                $partHeaders = $this->headers($piece[0] ?? '');

                if (! str_contains(strtolower($partHeaders['content-type'] ?? ''), 'text/plain')) {
                    continue;
                }

                return $this->decodeBody($piece[1] ?? '', $partHeaders['content-transfer-encoding'] ?? '');
            }
        }

        return $this->decodeBody($body, $headers['content-transfer-encoding'] ?? '');
    }

    private function decodeBody(string $body, string $encoding): string
    {
        $encoding = strtolower(trim($encoding));
        $body = rtrim($body, "\n");

        if ($encoding === 'base64') {
            $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?? '', true);

            return is_string($decoded) ? $decoded : '';
        }

        if ($encoding === 'quoted-printable') {
            return quoted_printable_decode($body);
        }

        return $body;
    }

    private function decodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $decoded = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return is_string($decoded) ? $decoded : $value;
    }
}
