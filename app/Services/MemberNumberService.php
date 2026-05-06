// app/Services/MemberNumberService.php
<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\DB;

class MemberNumberService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $lastMember = Member::lockForUpdate()->orderByDesc('member_number')->first();

            if ($lastMember) {
                $lastNum = (int) substr($lastMember->member_number, 3);
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }

            return sprintf('MBR%07d', $nextNum);
        });
    }
}