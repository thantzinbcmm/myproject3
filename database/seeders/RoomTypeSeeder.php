// database/seeders/RoomTypeSeeder.php
<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Facility::where('facility_code', 'BMM-MAIN')->first();
        if (!$facility) return;

        $roomTypes = [
            [
                'type_code' => 'SINGLE',
                'name_ja' => 'シングルルーム',
                'name_en' => 'Single Room',
                'name_zh_cn' => '单人间',
                'name_zh_tw' => '單人房',
                'name_ko' => '싱글룸',
                'name_my' => 'Single Room',
                'description_ja' => '落ち着いた雰囲気の一人旅に最適なシングルルームです。',
                'description_en' => 'A cozy single room perfect for solo travelers.',
                'standard_capacity' => 1,
                'max_capacity' => 1,
                'floor_area' => 20.00,
                'amenities' => ['WiFi', 'TV', 'バス・トイレ', 'エアコン', 'セーフティボックス'],
                'image_urls' => [],
                'sort_order' => 1,
            ],
            [
                'type_code' => 'DOUBLE',
                'name_ja' => 'ダブルルーム',
                'name_en' => 'Double Room',
                'name_zh_cn' => '双人间',
                'name_zh_tw' => '雙人房',
                'name_ko' => '더블룸',
                'name_my' => 'Double Room',
                'description_ja' => 'カップルやご夫婦に人気のダブルルームです。',
                'description_en' => 'A comfortable double room popular with couples.',
                'standard_capacity' => 2,
                'max_capacity' => 2,
                'floor_area' => 30.00,
                'amenities' => ['WiFi', 'TV', 'バス・トイレ', 'エアコン', 'セーフティボックス', 'ミニバー'],
                'image_urls' => [],
                'sort_order' => 2,
            ],
            [
                'type_code' => 'TWIN',
                'name_ja' => 'ツインルーム',
                'name_en' => 'Twin Room',
                'name_zh_cn' => '双床间',
                'name_zh_tw' => '雙床房',
                'name_ko' => '트윈룸',
                'name_my' => 'Twin Room',
                'description_ja' => 'ベッドが2台のツインルームです。ビジネス利用にも最適です。',
                'description_en' => 'A twin room with two beds, ideal for business travelers.',
                'standard_capacity' => 2,
                'max_capacity' => 3,
                'floor_area' => 35.00,
                'amenities' => ['WiFi', 'TV', 'バス・トイレ', 'エアコン', 'セーフティボックス', 'デスク'],
                'image_urls' => [],
                'sort_order' => 3,
            ],
            [
                'type_code' => 'SUITE',
                'name_ja' => 'スイートルーム',
                'name_en' => 'Suite Room',
                'name_zh_cn' => '套房',
                'name_zh_tw' => '套房',
                'name_ko' => '스위트룸',
                'name_my' => 'Suite Room',
                'description_ja' => '最上級の設備を誇る贅沢なスイートルームです。',
                'description_en' => 'A luxurious suite with top-class amenities.',
                'standard_capacity' => 2,
                'max_capacity' => 4,
                'floor_area' => 80.00,
                'amenities' => ['WiFi', 'TV', '独立バスルーム', 'ジャグジー', 'エアコン', 'セーフティボックス', 'ミニバー', 'ラウンジ'],
                'image_urls' => [],
                'sort_order' => 4,
            ],
        ];

        foreach ($roomTypes as $rtData) {
            RoomType::firstOrCreate(
                ['facility_id' => $facility->facility_id, 'type_code' => $rtData['type_code']],
                array_merge($rtData, ['facility_id' => $facility->facility_id, 'is_active' => true])
            );
        }
    }
}