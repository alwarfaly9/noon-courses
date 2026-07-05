<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Create a daily database backup and optionally upload to S3';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $filename = 'backup-' . date('Y-m-d-His') . '.sql.gz';
        $localPath = storage_path('app/backups/' . $filename);

        // Ensure directory exists
        if (!is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }

        // Build mysqldump command (password via environment variable to avoid shell exposure)
        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --host=%s --port=%s --user=%s --single-transaction --routines --triggers %s | gzip > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($localPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Database backup failed with exit code: ' . $returnCode);
            return Command::FAILURE;
        }

        $this->info("Local backup created: {$filename}");

        // Upload to S3 if configured
        if (config('filesystems.disks.s3.key')) {
            try {
                Storage::disk('s3')->put(
                    'backups/' . $filename,
                    file_get_contents($localPath)
                );
                $this->info("Backup uploaded to S3: backups/{$filename}");
            } catch (\Exception $e) {
                $this->error('S3 upload failed: ' . $e->getMessage());
            }
        }

        // Clean old local backups (keep last 7 days)
        $this->cleanOldBackups(storage_path('app/backups'), 7);

        return Command::SUCCESS;
    }

    private function cleanOldBackups(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->timestamp;

        foreach (glob($directory . '/backup-*.sql.gz') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $this->info('Deleted old backup: ' . basename($file));
            }
        }
    }
}
