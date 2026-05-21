<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseRestoreCommand extends Command
{
    protected $signature = 'db:restore {file : Path to .sql backup file} {--force : Skip confirmation}';

    protected $description = 'Restore MySQL database from a backup file (Member 3 lab)';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! File::exists($file)) {
            $this->error("Backup file not found: {$file}");

            return self::FAILURE;
        }

        $checksumFile = $file.'.sha256';
        if (File::exists($checksumFile)) {
            $expected = trim(explode(PHP_EOL, File::get($checksumFile))[0]);
            $actual = hash_file('sha256', $file);
            if ($expected !== $actual) {
                $this->error('Checksum mismatch — backup file may be corrupted.');

                return self::FAILURE;
            }
            $this->line('Checksum verified.');
        }

        if (! $this->option('force') && ! $this->confirm('This will overwrite data in the configured database. Continue?')) {
            return self::SUCCESS;
        }

        $connection = config('database.default');
        $config = config("database.connections.$connection");

        if (($config['driver'] ?? '') !== 'mysql') {
            $this->error('db:restore currently supports MySQL only.');

            return self::FAILURE;
        }

        $mysql = $this->resolveMysql();
        if ($mysql === null) {
            $this->error('mysql client not found. Set MYSQL_PATH in .env (e.g. C:\\xampp\\mysql\\bin\\mysql.exe)');

            return self::FAILURE;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $cmd = sprintf(
            '%s --host=%s --port=%s --user=%s %s < %s',
            escapeshellarg($mysql),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($file),
        );

        $env = $password !== '' ? 'MYSQL_PWD='.escapeshellarg($password).' ' : '';

        $code = 0;
        system($env.$cmd, $code);

        if ($code !== 0) {
            $this->error('Restore failed (exit code '.$code.').');

            return self::FAILURE;
        }

        $this->info('Database restored successfully from backup.');

        return self::SUCCESS;
    }

    private function resolveMysql(): ?string
    {
        $custom = env('MYSQL_PATH');
        if (is_string($custom) && $custom !== '' && is_executable($custom)) {
            return $custom;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
        ];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}
