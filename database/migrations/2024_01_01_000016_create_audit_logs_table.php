// database/migrations/2024_01_01_000016_create_audit_logs_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->uuid('admin_id')->nullable();
            $table->uuid('guest_id')->nullable();
            $table->string('action', 50);
            $table->string('resource', 50);
            $table->uuid('resource_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('admin_id', 'idx_admin_id');
            $table->index(['resource', 'resource_id'], 'idx_resource');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};