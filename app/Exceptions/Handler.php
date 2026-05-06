// app/Exceptions/Handler.php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });
    }

    public function render($request, Throwable $e): mixed
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($request, $e);
        }
        return parent::render($request, $e);
    }

    private function renderApiException($request, Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            $details = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $details[] = ['field' => $field, 'message' => $message];
                }
            }
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '入力内容に誤りがあります',
                    'details' => $details,
                ],
            ], 400);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => '認証が必要です',
                ],
            ], 401);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'リソースが見つかりません',
                ],
            ], 404);
        }

        if ($e instanceof InventoryConflictException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                ],
            ], 409);
        }

        if ($e instanceof BusinessException) {
            $statusCode = match ($e->getErrorCode()) {
                'INVALID_CREDENTIALS', 'TOKEN_EXPIRED' => 401,
                'PERMISSION_DENIED' => 403,
                'RESERVATION_NOT_FOUND' => 404,
                'INVENTORY_CONFLICT' => 409,
                'ACCOUNT_LOCKED' => 423,
                default => 422,
            };
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails(),
                ],
            ], $statusCode);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'メソッドが許可されていません'],
            ], 405);
        }

        \Illuminate\Support\Facades\Log::error('Unhandled exception', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_SERVER_ERROR',
                'message' => 'サーバーエラーが発生しました',
            ],
        ], 500);
    }
}