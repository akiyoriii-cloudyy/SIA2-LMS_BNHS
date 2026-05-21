<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {--path= : Custom output directory}';

    protected $description = 'Create a timestamped MySQL backup for disaster recovery (Member 3 lab)';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.$connection");

        if (($config['driver'] ?? '') !== 'mysql') {
            $this->error('db:backup currently supports MySQL only.');

            return self::FAILURE;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if ($database === '' || $username === '') {
            $this->error('Database credentials are not configured.');

            return self::FAILURE;
        }

        $outDir = $this->option('path')
            ? rtrim((string) $this->option('path'), DIRECTORY_SEPARATOR)
            : storage_path('app/backups');

        File::ensureDirectoryExists($outDir);

        $stamp = now()->format('Ymd_His');
        $file = $outDir.DIRECTORY_SEPARATOR."lms_bnhs_{$stamp}.sql";

        $mysqldump = $this->resolveMysqldump();
        if ($mysqldump === null) {
            $this->error('mysqldump not found. Add it to PATH or set MYSQLDUMP_PATH in .env');

            return self::FAILURE;
        }

        $args = [
            $mysqldump,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--result-file='.$file,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ];

        $env = [];
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = proc_open(
            $args,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env
        );

        if (! is_resource($process)) {
            $this->error('Could not start mysqldump.');

            return self::FAILURE;
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0 || ! File::exists($file)) {
            $this->error('Backup failed: '.trim($stderr ?: 'unknown error'));

            return self::FAILURE;
        }

        $checksum = hash_file('sha256', $file);
        File::put($file.'.sha256', $checksum.PHP_EOL.$file.PHP_EOL);

        $this->info("Backup created: {$file}");
        $this->line("SHA-256: {$checksum}");

        return self::SUCCESS;
    }

    private function resolveMysqldump(): ?string
    {
        $custom = env('MYSQLDUMP_PATH');
        if (is_string($custom) && $custom !== '' && is_executable($custom)) {
            return $custom;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        $which = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'which mysqldump';
        $found = trim((string) shell_exec($which));

        return $found !== '' && is_executable(explode(PHP_EOL, $found)[0]) ? explode(PHP_EOL, $found)[0] : null;
    }
}
