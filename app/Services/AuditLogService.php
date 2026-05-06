// app/Services/AuditLogService.php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    public function log(
        string $action,
        string $resource,
        ?string $resourceId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $actorId = null,
        bool $isAdmin = true,
    ): void {
        try {
            $request = app('request');

            AuditLog::create([
                'admin_id' => $isAdmin ? $actorId : null,
                'guest_id' => !$isAdmin ? $actorId : null,
                'action' => $action,
                'resource' => $resource,
                'resource_id' => $resourceId,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'ip_address' => $request instanceof Request ? $request->ip() : null,
                'user_agent' => $request instanceof Request ? $request->userAgent() : null,
            ]);
        } catch (\Throwable $e) {
            // 監査ログの失敗はシステムを止めない
            \Illuminate\Support\Facades\Log::error('Audit log failed: ' . $e->getMessage());
        }
    }
}