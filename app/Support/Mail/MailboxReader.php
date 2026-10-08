<?php

namespace App\Support\Mail;

interface MailboxReader
{
    /**
     * @return list<InboundMail>
     */
    public function unseen(): array;

    public function markSeen(string $uid): void;
}
