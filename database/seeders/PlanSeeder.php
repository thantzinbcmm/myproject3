// database/seeders/PlanSeeder.php
<?php

namespace Database\Seeders;

use App\Models\CancelPolicy;
use App\Models\Facility;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanRoomType;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::where('facility_code', 'BMM-MAIN')->first();
        if (!$facility) return;

        $cancelPolicy = CancelPolicy::where('facility_id', $facility->facility_id)
            ->where('is_default', true)
            ->first();

        $roomTypes = RoomType::where('facility_id', $facility->facility_id)->get()->keyBy('type_code');

        // スタンダードプラン
        $standardPlan = Plan::firstOrCreate(
            ['facility_id' => $facility->facility_id, 'plan_code' => 'STANDARD'],
            [
                'name_ja' => 'スタンダードプラン（素泊まり）',
                'name_en' => 'Standard Plan (Room Only)',
                'name_zh_cn' => '标准套餐（不含早餐）',
                'name_zh_tw' => '標準方案（不含早餐）',
                'name_ko' => '스탠다드 플랜（숙박만）',
                'name_my' => 'Standard Plan (Room Only)',
                'description_ja' => '素泊まりのスタンダードプランです。',
                'description_en' => 'Our standard room-only plan.',
                'meal_type' => 'NONE',
                'min_nights' => 1,
                'available_from' => now()->format('Y-m-d'),
                'available_to' => now()->addYears(2)->format('Y-m-d'),
                'cancel_policy_id' => $cancelPolicy?->cancel_policy_id,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // 朝食付きプラン
        $breakfastPlan = Plan::firstOrCreate(
            ['facility_id' => $facility->facility_id, 'plan_code' => 'BREAKFAST'],
            [
                'name_ja' => '朝食付きプラン',
                'name_en' => 'Breakfast Included Plan',
                'name_zh_cn' => '含早餐套餐',
                'name_zh_tw' => '含早餐方案',
                'name_ko' => '조식 포함 플랜',
                'name_my' => 'Breakfast Included Plan',
                'description_ja' => '和洋バイキングの朝食がついたお得なプランです。',
                'description_en' => 'Enjoy our Japanese and Western breakfast buffet.',
                'meal_type' => 'BREAKFAST',
                'min_nights' => 1,
                'available_from' => now()->format('Y-m-d'),
                'available_to' => now()->addYears(2)->format('Y-m-d'),
                'cancel_policy_id' => $cancelPolicy?->cancel_policy_id,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        // 客室タイプ関連付け & 料金設定
        $this->attachRoomTypesAndPrices($standardPlan, $roomTypes, $facility, false);
        $this->attachRoomTypesAndPrices($breakfastPlan, $roomTypes, $facility, true);
    }

    private function attachRoomTypesAndPrices(Plan $plan, $roomTypes, $facility, bool $withBreakfast): void
    {
        $basePrices = [
            'SINGLE' => 8000,
            'DOUBLE' => 12000,
            'TWIN' => 13000,
            'SUITE' => 30000,
        ];

        $breakfastExtra = 2000;

        foreach ($roomTypes as $typeCode => $roomType) {
            PlanRoomType::firstOrCreate(
                ['plan_id' => $plan->plan_id, 'room_type_id' => $roomType->room_type_id]
            );

            $basePrice = $basePrices[$typeCode] + ($withBreakfast ? $breakfastExtra : 0);

            // 平日料金
            PlanPrice::firstOrCreate(
                [
                    'plan_id' => $plan->plan_id,
                    'room_type_id' => $roomType->room_type_id,
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->addYears(2)->format('Y-m-d'),
                    'day_of_week' => 'MON,TUE,WED,THU',
                ],
                [
                    'base_price' => $basePrice,
                    'adult_price' => 0,
                    'child_price' => 0,
                    'is_active' => true,
                ]
            );

            // 週末料金（20%増）
            PlanPrice::firstOrCreate(
                [
                    'plan_id' => $plan->plan_id,
                    'room_type_id' => $roomType->room_type_id,
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->addYears(2)->format('Y-m-d'),
                    'day_of_week' => 'FRI,SAT,SUN',
                ],
                [
                    'base_price' => (int) ($basePrice * 1.2),
                    'adult_price' => 0,
                    'child_price' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}