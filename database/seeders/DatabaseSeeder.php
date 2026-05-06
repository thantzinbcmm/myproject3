// database/seeders/DatabaseSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminRoleSeeder::class,
            FacilitySeeder::class,
            AdminUserSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            CancelPolicySeeder::class,
            PlanSeeder::class,
            InventorySeeder::class,
        ]);
    }
}