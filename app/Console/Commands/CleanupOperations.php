<?php

namespace App\Console\Commands;

use App\Models\ReportExport;
use App\Support\Ops\BackupStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupOperations extends Command
{
    protected $signature = 'ops:cleanup';

    protected $description = 'Remove backups, report files, and failed jobs older than seven days';

    public function handle(BackupStore $store): int
    {
        $removed = $store->prune();
        $cutoff = now()->subDays((int) config('ops.retention_days'));

        ReportExport::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->each(function (ReportExport $export) use (&$removed) {
                if (filled($export->path)) {
                    Storage::disk('local')->delete($export->path);
                }

                $export->delete();
                $removed++;
            });

        $failed = DB::table('failed_jobs')->where('failed_at', '<', $cutoff)->delete();

        $this->info('Cleaned rows and files: '.($removed + $failed));

        return self::SUCCESS;
    }
}
