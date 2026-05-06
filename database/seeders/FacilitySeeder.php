// database/seeders/FacilitySeeder.php
<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        Facility::firstOrCreate(
            ['facility_code' => 'BMM-MAIN'],
            [
                'name_ja' => 'BMM ホテル 本館',
                'name_en' => 'BMM Hotel Main Building',
                'name_zh_cn' => 'BMM酒店 主楼',
                'name_zh_tw' => 'BMM飯店 主館',
                'name_ko' => 'BMM 호텔 본관',
                'name_my' => 'BMM ဟိုတယ် အဓိကအဆောက်အဦ',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-1-1',
                'phone_number' => '03-1234-5678',
                'email' => 'info@bmm-hotel.com',
                'check_in_time' => '15:00:00',
                'check_out_time' => '11:00:00',
                'is_active' => true,
            ]
        );
    }
}