// app/Services/ReservationNumberService.php
<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReservationNumberService
{
    public function generate(): string
    {
        $prefix = config('hotel.reservation_no_prefix', 'BMM');
        $date = now()->format('Ymd');

        return DB::transaction(function () use ($prefix, $date) {
            $key = "reservation_no_{$date}";

            // 今日の最大連番を取得
            $lastNo = Reservation::where('reservation_no', 'like', "{$prefix}-{$date}-%")
                ->lockForUpdate()
                ->orderByDesc('reservation_no')
                ->value('reservation_no');

            if ($lastNo) {
                $lastSeq = (int) substr($lastNo, -4);
                $nextSeq = $lastSeq + 1;
            } else {
                $nextSeq = 1;
            }

            return sprintf('%s-%s-%04d', $prefix, $date, $nextSeq);
        });
    }
}