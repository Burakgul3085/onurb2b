<?php

namespace App\Support\Mail;

final class InboundMail
{
    public function __construct(
        public string $uid,
        public string $messageId,
        public string $fromEmail,
        public string $fromName,
        public string $subject,
        public string $body,
    ) {}
}
