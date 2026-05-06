// database/seeders/AdminUserSeeder.php
<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = AdminRole::where('role_name', 'SUPER_ADMIN')->first();

        AdminUser::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'email' => 'superadmin@bmm-hotel.com',
                'password_hash' => Hash::make('Admin@12345!', ['rounds' => 12]),
                'last_name' => '管理',
                'first_name' => '太郎',
                'role_id' => $superAdminRole->role_id,
                'facility_id' => null,
                'is_active' => true,
                'password_changed_at' => now(),
            ]
        );

        $facilityAdminRole = AdminRole::where('role_name', 'FACILITY_ADMIN')->first();
        $facility = \App\Models\Facility::where('facility_code', 'BMM-MAIN')->first();

        if ($facility) {
            AdminUser::firstOrCreate(
                ['username' => 'facility_admin'],
                [
                    'email' => 'facility@bmm-hotel.com',
                    'password_hash' => Hash::make('Facility@12345!', ['rounds' => 12]),
                    'last_name' => '施設',
                    'first_name' => '管理者',
                    'role_id' => $facilityAdminRole->role_id,
                    'facility_id' => $facility->facility_id,
                    'is_active' => true,
                    'password_changed_at' => now(),
                ]
            );

            $frontStaffRole = AdminRole::where('role_name', 'FRONT_STAFF')->first();
            AdminUser::firstOrCreate(
                ['username' => 'front_staff'],
                [
                    'email' => 'front@bmm-hotel.com',
                    'password_hash' => Hash::make('Front@12345!', ['rounds' => 12]),
                    'last_name' => 'フロント',
                    'first_name' => 'スタッフ',
                    'role_id' => $frontStaffRole->role_id,
                    'facility_id' => $facility->facility_id,
                    'is_active' => true,
                    'password_changed_at' => now(),
                ]
            );
        }
    }
}