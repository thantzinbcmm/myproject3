// database/seeders/CancelPolicySeeder.php
<?php

namespace Database\Seeders;

use App\Models\CancelPolicy;
use App\Models\CancelPolicyRule;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class CancelPolicySeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::where('facility_code', 'BMM-MAIN')->first();
        if (!$facility) return;

        $policy = CancelPolicy::firstOrCreate(
            ['facility_id' => $facility->facility_id, 'name' => 'スタンダードキャンセルポリシー'],
            [
                'description' => 'チェックイン5日前まで無料、前日50%、当日・ノーショー100%',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $rules = [
            ['days_before' => 5, 'charge_rate' => 0.00, 'is_noshow' => false, 'sort_order' => 1],
            ['days_before' => 1, 'charge_rate' => 50.00, 'is_noshow' => false, 'sort_order' => 2],
            ['days_before' => 0, 'charge_rate' => 100.00, 'is_noshow' => false, 'sort_order' => 3],
            ['days_before' => 0, 'charge_rate' => 100.00, 'is_noshow' => true, 'sort_order' => 4],
        ];

        foreach ($rules as $rule) {
            CancelPolicyRule::firstOrCreate(
                [
                    'cancel_policy_id' => $policy->cancel_policy_id,
                    'days_before' => $rule['days_before'],
                    'is_noshow' => $rule['is_noshow'],
                ],
                $rule + ['cancel_policy_id' => $policy->cancel_policy_id]
            );
        }
    }
}