// database/seeders/AdminRoleSeeder.php
<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_name' => 'SUPER_ADMIN', 'description' => 'スーパー管理者'],
            ['role_name' => 'FACILITY_ADMIN', 'description' => '施設管理者'],
            ['role_name' => 'FRONT_STAFF', 'description' => 'フロントスタッフ'],
            ['role_name' => 'READONLY', 'description' => '参照専用'],
        ];

        foreach ($roles as $roleData) {
            AdminRole::firstOrCreate(['role_name' => $roleData['role_name']], $roleData);
        }

        $this->seedPermissions();
    }

    private function seedPermissions(): void
    {
        $superAdmin = AdminRole::where('role_name', 'SUPER_ADMIN')->first();
        $facilityAdmin = AdminRole::where('role_name', 'FACILITY_ADMIN')->first();
        $frontStaff = AdminRole::where('role_name', 'FRONT_STAFF')->first();
        $readonly = AdminRole::where('role_name', 'READONLY')->first();

        $permissions = [
            // SUPER_ADMIN - 全権限
            [$superAdmin->role_id, 'facility', 'CREATE'],
            [$superAdmin->role_id, 'facility', 'READ'],
            [$superAdmin->role_id, 'facility', 'UPDATE'],
            [$superAdmin->role_id, 'facility', 'DELETE'],
            [$superAdmin->role_id, 'room', 'CREATE'],
            [$superAdmin->role_id, 'room', 'READ'],
            [$superAdmin->role_id, 'room', 'UPDATE'],
            [$superAdmin->role_id, 'room', 'DELETE'],
            [$superAdmin->role_id, 'plan', 'CREATE'],
            [$superAdmin->role_id, 'plan', 'READ'],
            [$superAdmin->role_id, 'plan', 'UPDATE'],
            [$superAdmin->role_id, 'plan', 'DELETE'],
            [$superAdmin->role_id, 'reservation', 'CREATE'],
            [$superAdmin->role_id, 'reservation', 'READ'],
            [$superAdmin->role_id, 'reservation', 'UPDATE'],
            [$superAdmin->role_id, 'reservation', 'DELETE'],
            [$superAdmin->role_id, 'guest', 'CREATE'],
            [$superAdmin->role_id, 'guest', 'READ'],
            [$superAdmin->role_id, 'guest', 'UPDATE'],
            [$superAdmin->role_id, 'guest', 'DELETE'],
            [$superAdmin->role_id, 'admin_user', 'CREATE'],
            [$superAdmin->role_id, 'admin_user', 'READ'],
            [$superAdmin->role_id, 'admin_user', 'UPDATE'],
            [$superAdmin->role_id, 'admin_user', 'DELETE'],
            [$superAdmin->role_id, 'inventory', 'CREATE'],
            [$superAdmin->role_id, 'inventory', 'READ'],
            [$superAdmin->role_id, 'inventory', 'UPDATE'],
            [$superAdmin->role_id, 'inventory', 'DELETE'],
            [$superAdmin->role_id, 'report', 'READ'],
            [$superAdmin->role_id, 'member', 'CREATE'],
            [$superAdmin->role_id, 'member', 'READ'],
            [$superAdmin->role_id, 'member', 'UPDATE'],
            [$superAdmin->role_id, 'member', 'DELETE'],

            // FACILITY_ADMIN
            [$facilityAdmin->role_id, 'facility', 'READ'],
            [$facilityAdmin->role_id, 'room', 'CREATE'],
            [$facilityAdmin->role_id, 'room', 'READ'],
            [$facilityAdmin->role_id, 'room', 'UPDATE'],
            [$facilityAdmin->role_id, 'plan', 'CREATE'],
            [$facilityAdmin->role_id, 'plan', 'READ'],
            [$facilityAdmin->role_id, 'plan', 'UPDATE'],
            [$facilityAdmin->role_id, 'reservation', 'CREATE'],
            [$facilityAdmin->role_id, 'reservation', 'READ'],
            [$facilityAdmin->role_id, 'reservation', 'UPDATE'],
            [$facilityAdmin->role_id, 'guest', 'CREATE'],
            [$facilityAdmin->role_id, 'guest', 'READ'],
            [$facilityAdmin->role_id, 'guest', 'UPDATE'],
            [$facilityAdmin->role_id, 'inventory', 'CREATE'],
            [$facilityAdmin->role_id, 'inventory', 'READ'],
            [$facilityAdmin->role_id, 'inventory', 'UPDATE'],
            [$facilityAdmin->role_id, 'report', 'READ'],
            [$facilityAdmin->role_id, 'member', 'READ'],
            [$facilityAdmin->role_id, 'member', 'UPDATE'],

            // FRONT_STAFF
            [$frontStaff->role_id, 'room', 'READ'],
            [$frontStaff->role_id, 'plan', 'READ'],
            [$frontStaff->role_id, 'reservation', 'CREATE'],
            [$frontStaff->role_id, 'reservation', 'READ'],
            [$frontStaff->role_id, 'reservation', 'UPDATE'],
            [$frontStaff->role_id, 'guest', 'READ'],
            [$frontStaff->role_id, 'guest', 'UPDATE'],
            [$frontStaff->role_id, 'inventory', 'READ'],
            [$frontStaff->role_id, 'member', 'READ'],

            // READONLY
            [$readonly->role_id, 'facility', 'READ'],
            [$readonly->role_id, 'room', 'READ'],
            [$readonly->role_id, 'plan', 'READ'],
            [$readonly->role_id, 'reservation', 'READ'],
            [$readonly->role_id, 'guest', 'READ'],
            [$readonly->role_id, 'inventory', 'READ'],
            [$readonly->role_id, 'report', 'READ'],
            [$readonly->role_id, 'member', 'READ'],
        ];

        foreach ($permissions as [$roleId, $resource, $action]) {
            AdminRolePermission::firstOrCreate(
                ['role_id' => $roleId, 'resource' => $resource, 'action' => $action]
            );
        }
    }
}