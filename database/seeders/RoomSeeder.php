// database/seeders/RoomSeeder.php
<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::where('facility_code', 'BMM-MAIN')->first();
        if (!$facility) return;

        $roomTypes = RoomType::where('facility_id', $facility->facility_id)->get();

        $roomsConfig = [
            'SINGLE' => ['floors' => [2, 3], 'count_per_floor' => 5],
            'DOUBLE' => ['floors' => [4, 5], 'count_per_floor' => 5],
            'TWIN' => ['floors' => [6, 7], 'count_per_floor' => 4],
            'SUITE' => ['floors' => [8], 'count_per_floor' => 2],
        ];

        foreach ($roomTypes as $roomType) {
            $config = $roomsConfig[$roomType->type_code] ?? null;
            if (!$config) continue;

            foreach ($config['floors'] as $floor) {
                for ($i = 1; $i <= $config['count_per_floor']; $i++) {
                    $roomNumber = sprintf('%d%02d', $floor, $i);
                    Room::firstOrCreate(
                        ['facility_id' => $facility->facility_id, 'room_number' => $roomNumber],
                        [
                            'room_type_id' => $roomType->room_type_id,
                            'facility_id' => $facility->facility_id,
                            'floor' => $floor,
                            'status' => 'AVAILABLE',
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}