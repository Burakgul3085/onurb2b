<?php

namespace App\Support\Ops;

use Illuminate\Support\Facades\File;

class BackupStore
{
    public function directory(): string
    {
        return (string) config('ops.backup_path');
    }

    public function prune(): int
    {
        $directory = $this->directory();

        if (! File::isDirectory($directory)) {
            return 0;
        }

        $cutoff = now()->subDays((int) config('ops.retention_days'))->getTimestamp();
        $removed = 0;

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'sql' || $file->getMTime() >= $cutoff) {
                continue;
            }

            File::delete($file->getPathname());
            $removed++;
        }

        return $removed;
    }
}
