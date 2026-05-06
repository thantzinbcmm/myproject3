// database/migrations/2024_01_01_000002_create_admin_roles_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->uuid('role_id')->primary();
            $table->enum('role_name', ['SUPER_ADMIN', 'FACILITY_ADMIN', 'FRONT_STAFF', 'READONLY'])->unique();
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('admin_role_permissions', function (Blueprint $table) {
            $table->uuid('permission_id')->primary();
            $table->uuid('role_id');
            $table->string('resource', 50);
            $table->string('action', 20);
            $table->foreign('role_id')->references('role_id')->on('admin_roles');
            $table->unique(['role_id', 'resource', 'action'], 'uq_role_resource_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_permissions');
        Schema::dropIfExists('admin_roles');
    }
};