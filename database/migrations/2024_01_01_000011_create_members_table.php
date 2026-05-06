// database/migrations/2024_01_01_000011_create_members_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('member_id')->primary();
            $table->uuid('guest_id')->unique();
            $table->string('member_number', 20)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->enum('member_rank', ['STANDARD', 'SILVER', 'GOLD', 'PLATINUM'])->default('STANDARD');
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_failed_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('guest_id')->references('guest_id')->on('guests');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};