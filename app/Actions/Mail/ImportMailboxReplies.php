<?php

namespace App\Actions\Mail;

use App\Models\Message;
use App\Models\MessageThread;
use App\Support\Mail\InboundMailParser;
use App\Support\Mail\MailboxReader;
use Illuminate\Support\Str;

class ImportMailboxReplies
{
    public function __construct(
        private MailboxReader $mailbox,
        private InboundMailParser $parser,
    ) {}

    public function execute(): int
    {
        $imported = 0;
        $ownAddress = strtolower((string) config('mail.mailers.smtp.username'));

        foreach ($this->mailbox->unseen() as $mail) {
            $token = $this->parser->token($mail->subject);
            $thread = $token === null ? null : MessageThread::query()->where('mail_token', $token)->first();
            $alreadyStored = Message::query()->where('external_message_id', $mail->messageId)->exists();
            $fromUs = $ownAddress !== '' && strcasecmp($mail->fromEmail, $ownAddress) === 0;

            if ($thread !== null && ! $alreadyStored && ! $fromUs) {
                $thread->messages()->create([
                    'user_id' => null,
                    'sender_name' => $mail->fromName !== '' ? $mail->fromName : $mail->fromEmail,
                    'sender_email' => $mail->fromEmail,
                    'body' => Str::limit(trim($mail->body), 8000, ''),
                    'external_message_id' => $mail->messageId,
                ]);
                $this->mailbox->markSeen($mail->uid);
                $imported++;
            }
        }

        return $imported;
    }
}
