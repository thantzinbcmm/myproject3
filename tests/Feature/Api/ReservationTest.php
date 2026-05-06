// tests/Feature/Api/ReservationTest.php
<?php

namespace Tests\Feature\Api;

use App\Models\CancelPolicy;
use App\Models\Facility;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanRoomType;
use App\Models\Reservation;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private RoomType $roomType;
    private Plan $plan;
    private CancelPolicy $cancelPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestData();
    }

    private function setUpTestData(): void
    {
        $this->facility = Facility::create([
            'facility_code' => 'TEST-001',
            'name_ja' => 'テストホテル',
            'name_en' => 'Test Hotel',
            'address' => '東京都テスト区1-1-1',
            'phone_number' => '03-0000-0000',
            'check_in_time' => '15:00:00',
            'check_out_time' => '11:00:00',
        ]);

        $this->roomType = RoomType::create([
            'facility_id' => $this->facility->facility_id,
            'type_code' => 'SINGLE',
            'name_ja' => 'シングルルーム',
            'name_en' => 'Single Room',
            'standard_capacity' => 1,
            'max_capacity' => 1,
            'is_active' => true,
        ]);

        $this->cancelPolicy = CancelPolicy::create([
            'facility_id' => $this->facility->facility_id,
            'name' => 'デフォルトポリシー',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->plan = Plan::create([
            'facility_id' => $this->facility->facility_id,
            'plan_code' => 'STD',
            'name_ja' => 'スタンダードプラン',
            'name_en' => 'Standard Plan',
            'meal_type' => 'NONE',
            'min_nights' => 1,
            'cancel_policy_id' => $this->cancelPolicy->cancel_policy_id,
            'is_public' => true,
            'is_active' => true,
        ]);

        PlanRoomType::create([
            'plan_id' => $this->plan->plan_id,
            'room_type_id' => $this->roomType->room_type_id,
        ]);

        // 料金設定
        PlanPrice::create([
            'plan_id' => $this->plan->plan_id,
            'room_type_id' => $this->roomType->room_type_id,
            'start_date' => now()->subYear()->format('Y-m-d'),
            'end_date' => now()->addYears(2)->format('Y-m-d'),
            'day_of_week' => 'MON,TUE,WED,THU,FRI,SAT,SUN',
            'base_price' => 10000,
            'adult_price' => 0,
            'child_price' => 0,
            'is_active' => true,
        ]);

        // 在庫設定
        for ($i = 0; $i < 30; $i++) {
            Inventory::create([
                'room_type_id' => $this->roomType->room_type_id,
                'facility_id' => $this->facility->facility_id,
                'date' => now()->addDays($i)->format('Y-m-d'),
                'total_count' => 5,
                'booked_count' => 0,
                'closed_count' => 0,
                'stop_sale' => false,
                'version' => 0,
            ]);
        }
    }

    public function test_can_create_reservation(): void
    {
        $checkIn = now()->addDays(5)->format('Y-m-d');
        $checkOut = now()->addDays(7)->format('Y-m-d');

        $response = $this->postJson('/api/v1/reservations', [
            'facility_id' => $this->facility->facility_id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'room_type_id' => $this->roomType->room_type_id,
            'plan_id' => $this->plan->plan_id,
            'adult_count' => 1,
            'child_count' => 0,
            'guest' => [
                'last_name' => '山田',
                'first_name' => '太郎',
                'email' => 'taro@test.com',
                'phone' => '090-1234-5678',
            ],
            'cancel_policy_agreed' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'reservationId',
                    'reservationNo',
                    'status',
                    'totalAmount',
                ],
            ]);

        $this->assertDatabaseHas('reservations', [
            'facility_id' => $this->facility->facility_id,
            'status' => 'CONFIRMED',
            'total_amount' => 20000, // 10000 × 2泊
        ]);

        // 在庫が減っていることを確認
        $this->assertDatabaseHas('inventory', [
            'room_type_id' => $this->roomType->room_type_id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'booked_count' => 1,
        ]);
    }

    public function test_cannot_reserve_past_date(): void
    {
        $response = $this->postJson('/api/v1/reservations', [
            'facility_id' => $this->facility->facility_id,
            'check_in_date' => now()->subDay()->format('Y-m-d'),
            'check_out_date' => now()->format('Y-m-d'),
            'room_type_id' => $this->roomType->room_type_id,
            'plan_id' => $this->plan->plan_id,
            'adult_count' => 1,
            'guest' => [
                'last_name' => 'Test',
                'first_name' => 'User',
                'email' => 'test@test.com',
                'phone' => '090-0000-0000',
            ],
            'cancel_policy_agreed' => true,
        ]);

        $response->assertStatus(400);
    }

    public function test_can_lookup_reservation_by_no_and_email(): void
    {
        $guest = Guest::create([
            'last_name' => '佐藤',
            'first_name' => '花子',
            'email' => 'hanako@test.com',
            'phone' => '080-1111-2222',
        ]);

        $reservation = Reservation::create([
            'reservation_no' => 'BMM-20240101-0001',
            'facility_id' => $this->facility->facility_id,
            'guest_id' => $guest->guest_id,
            'channel' => 'DIRECT',
            'status' => 'CONFIRMED',
            'check_in_date' => now()->addDays(5)->format('Y-m-d'),
            'check_out_date' => now()->addDays(7)->format('Y-m-d'),
            'nights' => 2,
            'adult_count' => 1,
            'total_amount' => 20000,
            'confirmed_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/reservations/BMM-20240101-0001?email=hanako@test.com');

        $response->assertStatus(200)
            ->assertJsonPath('data.reservationNo', 'BMM-20240101-0001');
    }

    public function test_cannot_access_reservation_with_wrong_email(): void
    {
        $guest = Guest::create([
            'last_name' => 'テスト',
            'first_name' => 'ユーザー',
            'email' => 'correct@test.com',
            'phone' => '090-0000-0000',
        ]);

        Reservation::create([
            'reservation_no' => 'BMM-20240101-0002',
            'facility_id' => $this->facility->facility_id,
            'guest_id' => $guest->guest_id,
            'channel' => 'DIRECT',
            'status' => 'CONFIRMED',
            'check_in_date' => now()->addDays(5)->format('Y-m-d'),
            'check_out_date' => now()->addDays(7)->format('Y-m-d'),
            'nights' => 2,
            'adult_count' => 1,
            'total_amount' => 20000,
            'confirmed_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/reservations/BMM-20240101-0002?email=wrong@test.com');
        $response->assertStatus(403);
    }
}