// database/seeders/InventorySeeder.php
<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Inventory;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::where('facility_code', 'BMM-MAIN')->first();
        if (!$facility) return;

        $roomTypes = RoomType::where('facility_id', $facility->facility_id)->get();

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addYear();

        foreach ($roomTypes as $roomType) {
            // 客室数を取得
            $roomCount = Room::where('room_type_id', $roomType->room_type_id)
                ->where('is_active', true)
                ->count();

            if ($roomCount === 0) continue;

            $date = $startDate->copy();
            while ($date->lte($endDate)) {
                Inventory::firstOrCreate(
                    [
                        'room_type_id' => $roomType->room_type_id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'facility_id' => $facility->facility_id,
                        'total_count' => $roomCount,
                        'booked_count' => 0,
                        'closed_count' => 0,
                        'stop_sale' => false,
                        'version' => 0,
                    ]
                );
                $date->addDay();
            }
        }
    }
}