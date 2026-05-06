// app/Console/Commands/BackupDatabase.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--dry-run : Run without actually creating backup}';
    protected $description = 'バックアップを実行します（毎日03:00 JST）';

    public function handle(): int
    {
        $this->info('データベースバックアップを開始します...');
        $timestamp = now()->format('YmdHis');
        $filename = "backup-{$timestamp}.sql.gz";
        $tmpPath = storage_path("app/backups/{$filename}");

        try {
            if (!$this->option('dry-run')) {
                $this->createBackup($tmpPath);
                $this->uploadToStorage($tmpPath, $filename);
                $this->cleanOldBackups();
                $this->cleanup($tmpPath);
            }

            Log::info("バックアップ完了: {$filename}");
            $this->info("バックアップ完了: {$filename}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('バックアップ失敗: ' . $e->getMessage());
            $this->error('バックアップ失敗: ' . $e->getMessage());
            $this->notifyAdmins($e->getMessage());
            return self::FAILURE;
        }
    }

    private function createBackup(string $tmpPath): void
    {
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        $db = config('database.connections.mysql');
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s | gzip > %s',
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['host']),
            escapeshellarg($db['port']),
            escapeshellarg($db['database']),
            escapeshellarg($tmpPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("mysqldump failed with exit code: {$returnCode}");
        }
    }

    private function uploadToStorage(string $localPath, string $filename): void
    {
        $disk = Storage::disk(config('hotel.backup.disk', 'local'));
        $disk->put("backups/{$filename}", file_get_contents($localPath));
    }

    private function cleanOldBackups(): void
    {
        $retentionDays = config('hotel.backup.retention_days', 7);
        $disk = Storage::disk(config('hotel.backup.disk', 'local'));
        $files = $disk->files('backups');

        foreach ($files as $file) {
            $lastModified = $disk->lastModified($file);
            if (time() - $lastModified > $retentionDays * 86400) {
                $disk->delete($file);
                Log::info("古いバックアップを削除: {$file}");
            }
        }
    }

    private function cleanup(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function notifyAdmins(string $message): void
    {
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "バックアップ失敗: {$message}",
                fn($m) => $m->to(config('mail.from.address'))->subject('【緊急】バックアップ失敗通知')
            );
        } catch (\Throwable) {}
    }
}