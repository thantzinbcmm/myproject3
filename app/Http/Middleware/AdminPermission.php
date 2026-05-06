// app/Http/Middleware/AdminPermission.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        $admin = auth('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'TOKEN_EXPIRED', 'message' => '認証が必要です'],
            ], 401);
        }

        if (!$admin->hasPermission($resource, $action)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'PERMISSION_DENIED', 'message' => 'この操作を行う権限がありません'],
            ], 403);
        }

        return $next($request);
    }
}