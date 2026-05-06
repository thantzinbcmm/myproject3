// app/Services/GroupReservationNumberService.php
<?php

namespace App\Services;

use App\Models\GroupReservation;
use Illuminate\Support\Facades\DB;

class GroupReservationNumberService
{
    public function generate(): string
    {
        $prefix = 'GRP';
        $date = now()->format('Ymd');

        return DB::transaction(function () use ($prefix, $date) {
            $lastNo = GroupReservation::where('group_reservation_no', 'like', "{$prefix}-{$date}-%")
                ->lockForUpdate()
                ->orderByDesc('group_reservation_no')
                ->value('group_reservation_no');

            $nextSeq = $lastNo ? ((int) substr($lastNo, -4)) + 1 : 1;
            return sprintf('%s-%s-%04d', $prefix, $date, $nextSeq);
        });
    }
}