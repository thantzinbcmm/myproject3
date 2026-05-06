// database/migrations/2024_01_01_000003_create_admin_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->uuid('admin_id')->primary();
            $table->string('username', 50)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->uuid('role_id');
            $table->uuid('facility_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_failed_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            $table->foreign('role_id')->references('role_id')->on('admin_roles');
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};