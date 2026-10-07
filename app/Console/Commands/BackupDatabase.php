<?php

namespace App\Console\Commands;

use App\Support\Ops\BackupStore;
use App\Support\Ops\DatabaseDumper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'ops:backup';

    protected $description = 'Dump the database and keep seven days of backups';

    public function handle(DatabaseDumper $dumper, BackupStore $store): int
    {
        $directory = $store->directory();
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'onurb2b-'.now()->format('Ymd-His').'.sql';
        $dumper->dump($path);
        $removed = $store->prune();

        $this->info($path);
        $this->info("Old backups removed: {$removed}");

        return self::SUCCESS;
    }
}
