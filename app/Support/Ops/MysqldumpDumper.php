<?php

namespace App\Support\Ops;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class MysqldumpDumper implements DatabaseDumper
{
    public function dump(string $path): void
    {
        $name = (string) config('database.default');
        $config = config("database.connections.$name");

        if (! is_array($config) || ($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Database backup supports MySQL or MariaDB.');
        }

        $command = [
            (string) config('ops.dump_binary'),
            '--host='.$config['host'],
            '--port='.(string) $config['port'],
            '--user='.(string) $config['username'],
            '--single-transaction',
            '--skip-lock-tables',
            (string) $config['database'],
        ];

        $env = [];

        if (filled($config['password'] ?? null)) {
            $env['MYSQL_PWD'] = (string) $config['password'];
        }

        $process = new Process($command, null, $env);
        $process->setTimeout(120);
        $process->mustRun();

        File::put($path, $process->getOutput());
    }
}
