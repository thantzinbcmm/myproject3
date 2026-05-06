// app/Http/Controllers/Api/AuthController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\AdminLoginRequest;
use App\Services\AuthService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * POST /api/v1/auth/login
     */
    public function memberLogin(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->memberLogin(
            $request->email,
            $request->password
        );

        $this->auditLogService->log(
            action: 'LOGIN',
            resource: 'member',
            isAdmin: false,
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/v1/auth/admin/login
     */
    public function adminLogin(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->authService->adminLogin(
            $request->username,
            $request->password
        );

        $this->auditLogService->log(
            action: 'LOGIN',
            resource: 'admin_user',
            resourceId: $result['admin']['adminId'],
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        try {
            $guard = auth('admin')->check() ? 'admin' : 'member';
            $token = auth($guard)->refresh();
            return response()->json([
                'success' => true,
                'data' => ['accessToken' => $token],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'TOKEN_EXPIRED', 'message' => 'トークンが無効です'],
            ], 401);
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $guard = $request->route()->getPrefix() === 'admin' ? 'admin' : 'member';
            auth($guard)->logout();
        } catch (\Exception) {
        }
        return response()->json(null, 204);
    }
}