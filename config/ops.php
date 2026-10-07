<?php

return [
    'dump_binary' => env('BACKUP_DUMP_BINARY', 'mysqldump'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 7),
    'backup_path' => storage_path('app/backups'),
];
