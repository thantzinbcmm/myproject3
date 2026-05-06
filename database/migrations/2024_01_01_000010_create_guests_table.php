// database/migrations/2024_01_01_000010_create_guests_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('guest_id')->primary();
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('last_name_kana', 50)->nullable();
            $table->string('first_name_kana', 50)->nullable();
            $table->string('last_name_en', 50)->nullable();
            $table->string('first_name_en', 50)->nullable();
            $table->string('email', 255);
            $table->string('phone', 20);
            $table->string('nationality', 2)->nullable();
            $table->string('preferred_language', 10)->default('ja');
            $table->string('postal_code', 10)->nullable();
            $table->string('address', 255)->nullable();
            $table->boolean('is_anonymized')->default(false);
            $table->timestamps();
            $table->index('email', 'idx_email');
            $table->index('phone', 'idx_phone');
            $table->index(['email', 'phone', 'last_name', 'first_name'], 'idx_guest_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};