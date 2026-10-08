<?php

namespace App\Console\Commands;

use App\Actions\Mail\ImportMailboxReplies;
use Illuminate\Console\Command;

class ImportMailbox extends Command
{
    protected $signature = 'ops:import-mail';

    protected $description = 'Import mailbox replies into message threads';

    public function handle(ImportMailboxReplies $import): int
    {
        $count = $import->execute();
        $this->info("Imported {$count} replies.");

        return self::SUCCESS;
    }
}
