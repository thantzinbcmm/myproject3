// tests/Feature/Api/InventoryTest.php
<?php

namespace Tests\Feature\Api;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Facility;
use App\Models\Inventory;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;
    private Facility $facility;
    private RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create([
            'role_name' => 'FACILITY_ADMIN',
            'description' => 'Facility admin',
        ]);

        $this->facility = Facility::create([
            'facility_code' => 'TEST-F01',
            'name_ja' => 'テスト施設',
            'name_en' => 'Test Facility',
            'address' => 'Test Address',
            'phone_number' => '03-0000-0000',
            'check_in_time' => '15:00:00',
            'check_out_time' => '11:00:00',
        ]);

        $this->admin = AdminUser::create([
            'username' => 'inv_admin',
            'email' => 'inv@test.com',
            'password_hash' => Hash::make('Test@12345'),
            'last_name' => 'Test',
            'first_name' => 'Admin',
            'role_id' => $role->role_id,
            'facility_id' => $this->facility->facility_id,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'facility_id' => $this->facility->facility_id,
            'type_code' => 'DBL',
            'name_ja' => 'ダブル',
            'name_en' => 'Double',
            'standard_capacity' => 2,
            'max_capacity' => 2,
            'is_active' => true,
        ]);

        Inventory::create([
            'room_type_id' => $this->roomType->room_type_id,
            'facility_id' => $this->facility->facility_id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'total_count' => 5,
            'booked_count' => 0,
            'closed_count' => 0,
            'stop_sale' => false,
            'version' => 0,
        ]);
    }

    public function test_admin_can_update_inventory(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/admin/inventory', [
                'facility_id' => $this->facility->facility_id,
                'room_type_id' => $this->roomType->room_type_id,
                'updates' => [
                    [
                        'date' => now()->addDays(5)->format('Y-m-d'),
                        'closed_count' => 2,
                        'stop_sale' => false,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('inventory', [
            'room_type_id' => $this->roomType->room_type_id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'closed_count' => 2,
        ]);
    }
}