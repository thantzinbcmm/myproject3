// app/Services/CancelFeeService.php
<?php

namespace App\Services;

use Carbon\Carbon;

class CancelFeeService
{
    /**
     * キャンセル料計算
     */
    public function calculate(
        Carbon $checkInDate,
        int $totalAmount,
        ?Carbon $cancelDate = null
    ): array {
        $cancelDate = $cancelDate ?? now()->startOfDay();
        $daysBeforeCheckin = $cancelDate->startOfDay()->diffInDays($checkInDate->startOfDay(), false);

        // ノーショー（チェックイン日以降）
        if ($daysBeforeCheckin < 0) {
            return [
                'rate' => 100,
                'fee' => $totalAmount,
                'policy' => 'NOSHOW',
            ];
        }

        // 当日
        if ($daysBeforeCheckin === 0) {
            return [
                'rate' => 100,
                'fee' => $totalAmount,
                'policy' => 'SAME_DAY',
            ];
        }

        // 前日（1日前）
        if ($daysBeforeCheckin === 1) {
            return [
                'rate' => 50,
                'fee' => (int) floor($totalAmount * 0.5),
                'policy' => 'DAY_BEFORE',
            ];
        }

        // 5日前以前 = 無料
        return [
            'rate' => 0,
            'fee' => 0,
            'policy' => 'FREE',
        ];
    }

    /**
     * 無料キャンセル期限日を取得
     */
    public function getFreeCancelUntil(Carbon $checkInDate): Carbon
    {
        return $checkInDate->copy()->subDays(2);
    }
}