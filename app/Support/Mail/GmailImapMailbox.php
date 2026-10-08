<?php

namespace App\Support\Mail;

use RuntimeException;

class GmailImapMailbox implements MailboxReader
{
    /** @var resource|null */
    private $socket = null;

    private int $tag = 0;

    public function __construct(private InboundMailParser $parser) {}

    public function unseen(): array
    {
        $this->connect();
        $this->command('SELECT INBOX');
        $search = $this->command('UID SEARCH UNSEEN SUBJECT "OB-"');
        preg_match_all('/\d+/', $this->untagged($search, 'SEARCH'), $matches);
        $mails = [];

        foreach (array_slice($matches[0], -20) as $uid) {
            $fetched = $this->command('UID FETCH '.$uid.' BODY.PEEK[]');
            $raw = $this->literal($fetched);
            $mail = $raw === null ? null : $this->parser->message($uid, $raw);

            if ($mail !== null) {
                $mails[] = $mail;
            }
        }

        $this->close();

        return $mails;
    }

    public function markSeen(string $uid): void
    {
        $this->connect();
        $this->command('UID STORE '.$uid.' +FLAGS.SILENT (\Seen)');
        $this->close();
    }

    private function connect(): void
    {
        if (is_resource($this->socket)) {
            return;
        }

        $username = (string) config('mail.mailers.smtp.username');
        $password = (string) config('mail.mailers.smtp.password');

        if ($username === '' || $password === '') {
            throw new RuntimeException('Mailbox is not configured.');
        }

        $socket = stream_socket_client('ssl://imap.gmail.com:993', $errorCode, $errorMessage, 15);

        if ($socket === false) {
            throw new RuntimeException('Mailbox connection failed.');
        }

        stream_set_timeout($socket, 12);
        $this->socket = $socket;
        $this->read();
        $this->command('LOGIN '.$this->quote($username).' '.$this->quote($password));
    }

    private function command(string $command): string
    {
        $this->tag++;
        $tag = 'A'.$this->tag;
        fwrite($this->socket, $tag.' '.$command."\r\n");
        $response = '';

        do {
            $line = $this->read();
            $response .= $line;
        } while (! preg_match('/^'.$tag.' (OK|NO|BAD)/m', $response, $match));

        if ($match[1] !== 'OK') {
            throw new RuntimeException('Mailbox command failed.');
        }

        return $response;
    }

    private function read(): string
    {
        $line = fgets($this->socket);
        $this->guard();

        if ($line === false) {
            throw new RuntimeException('Mailbox closed.');
        }

        if (preg_match('/\{(\d+)\}\r\n$/', $line, $match) === 1) {
            $literal = $this->readBytes((int) $match[1]);
            $tail = fgets($this->socket);
            $this->guard();
            $line .= $literal.($tail === false ? '' : $tail);
        }

        return $line;
    }

    private function untagged(string $response, string $name): string
    {
        if (preg_match('/^\* '.$name.' (.*)$/m', $response, $match) !== 1) {
            return '';
        }

        return $match[1];
    }

    private function literal(string $response): ?string
    {
        $start = strpos($response, "\r\n");

        if ($start === false) {
            return null;
        }

        $body = substr($response, $start + 2);
        $end = strrpos($body, "\r\nA");
        $raw = $end === false ? $body : substr($body, 0, $end);

        return trim(preg_replace('/\)\s*$/', '', $raw) ?? $raw);
    }

    private function readBytes(int $length): string
    {
        $data = '';

        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            $this->guard();

            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Mailbox closed.');
            }

            $data .= $chunk;
        }

        return $data;
    }

    private function guard(): void
    {
        $meta = stream_get_meta_data($this->socket);

        if (($meta['timed_out'] ?? false) === true) {
            throw new RuntimeException('Mailbox timed out.');
        }
    }

    private function quote(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    private function close(): void
    {
        if (! is_resource($this->socket)) {
            return;
        }

        fwrite($this->socket, "A99 LOGOUT\r\n");
        fclose($this->socket);
        $this->socket = null;
    }
}
