// app/Console/Commands/CleanAuditLogs.php
<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class CleanAuditLogs extends Command
{
    protected $signature = 'audit:clean {--days=365 : Days to retain}';
    protected $description = '期限切れの監査ログを削除します';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = AuditLog::where('created_at', '<', $cutoff)->delete();
        $this->info("監査ログを削除しました: {$deleted}件（{$days}日以前）");

        return self::SUCCESS;
    }
}