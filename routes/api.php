// routes/api.php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomSearchController;
use App\Http\Controllers\Api\Admin\AdminFacilityController;
use App\Http\Controllers\Api\Admin\AdminGroupReservationController;
use App\Http\Controllers\Api\Admin\AdminInventoryController;
use App\Http\Controllers\Api\Admin\AdminMemberController;
use App\Http\Controllers\Api\Admin\AdminPlanController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminReservationController;
use App\Http\Controllers\Api\Admin\AdminRoomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ==================== 認証 ====================
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'memberLogin'])
            ->middleware('throttle:' . config('hotel.rate_limits.login') . ',1');
        Route::post('/admin/login', [AuthController::class, 'adminLogin'])
            ->middleware('throttle:' . config('hotel.rate_limits.login') . ',1');
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware(['auth:member,admin']);
    });

    // ==================== 施設（公開） ====================
    Route::prefix('facilities')->middleware('throttle:' . config('hotel.rate_limits.search') . ',1')->group(function () {
        Route::get('/', [FacilityController::class, 'index']);
        Route::get('/{facilityId}', [FacilityController::class, 'show']);
        Route::get('/{facilityId}/rooms/search', [RoomSearchController::class, 'search']);
        Route::get('/{facilityId}/inventory/calendar', [RoomSearchController::class, 'calendar']);
    });

    // ==================== 予約（公開・本人確認） ====================
    Route::prefix('reservations')
        ->middleware('throttle:' . config('hotel.rate_limits.api') . ',1')
        ->group(function () {
            Route::post('/', [ReservationController::class, 'store'])
                ->middleware('throttle:' . config('hotel.rate_limits.booking') . ',1');
            Route::get('/{reservationNo}', [ReservationController::class, 'show']);
            Route::put('/{reservationId}/cancel', [ReservationController::class, 'cancel']);
            Route::put('/{reservationId}/change', [ReservationController::class, 'change']);
        });

    // ==================== 会員（認証） ====================
    Route::prefix('members')->group(function () {
        Route::post('/register', [MemberController::class, 'register'])
            ->middleware('throttle:5,1');

        Route::middleware('auth:member')->group(function () {
            Route::get('/me', [MemberController::class, 'me']);
            Route::get('/me/reservations', [MemberController::class, 'myReservations']);
        });
    });

    // ==================== 管理者API ====================
    Route::prefix('admin')
        ->middleware(['auth:admin'])
        ->group(function () {

            // 施設管理
            Route::prefix('facilities')->group(function () {
                Route::get('/', [AdminFacilityController::class, 'index']);
                Route::post('/', [AdminFacilityController::class, 'store']);
                Route::put('/{facilityId}', [AdminFacilityController::class, 'update']);
            });

            // 客室管理
            Route::prefix('rooms')->group(function () {
                Route::get('/', [AdminRoomController::class, 'index']);
                Route::post('/', [AdminRoomController::class, 'store']);
                Route::put('/{roomId}', [AdminRoomController::class, 'update']);
            });

            // プラン管理
            Route::prefix('plans')->group(function () {
                Route::get('/', [AdminPlanController::class, 'index']);
                Route::post('/', [AdminPlanController::class, 'store']);
                Route::put('/{planId}', [AdminPlanController::class, 'update']);
                Route::put('/{planId}/prices', [AdminPlanController::class, 'updatePrices']);
            });

            // 在庫管理
            Route::prefix('inventory')->group(function () {
                Route::put('/', [AdminInventoryController::class, 'update']);
                Route::get('/calendar', [AdminInventoryController::class, 'calendar']);
            });

            // 予約管理
            Route::prefix('reservations')->group(function () {
                Route::get('/', [AdminReservationController::class, 'index']);
                Route::post('/', [AdminReservationController::class, 'store']);
                Route::get('/{reservationId}', [AdminReservationController::class, 'show']);
                Route::put('/{reservationId}/cancel', [AdminReservationController::class, 'cancel']);
                Route::put('/{reservationId}/checkin', [AdminReservationController::class, 'checkIn']);
                Route::put('/{reservationId}/checkout', [AdminReservationController::class, 'checkOut']);
                Route::patch('/{reservationId}/notes', [AdminReservationController::class, 'updateNotes']);
            });

            // グループ予約
            Route::prefix('group-reservations')->group(function () {
                Route::get('/', [AdminGroupReservationController::class, 'index']);
                Route::post('/', [AdminGroupReservationController::class, 'store']);
            });

            // 会員管理
            Route::prefix('members')->group(function () {
                Route::get('/', [AdminMemberController::class, 'index']);
                Route::delete('/{memberId}/anonymize', [AdminMemberController::class, 'anonymize']);
            });

            // レポート
            Route::prefix('reports')->group(function () {
                Route::get('/dashboard', [AdminReportController::class, 'dashboard']);
                Route::get('/revenue', [AdminReportController::class, 'revenueReport']);
            });
        });
});