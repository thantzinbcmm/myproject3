// database/migrations/2024_01_01_000001_create_facilities_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->uuid('facility_id')->primary();
            $table->string('facility_code', 20)->unique();
            $table->string('name_ja', 100);
            $table->string('name_en', 100);
            $table->string('name_zh_cn', 100)->nullable();
            $table->string('name_zh_tw', 100)->nullable();
            $table->string('name_ko', 100)->nullable();
            $table->string('name_my', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('address', 255);
            $table->string('phone_number', 20);
            $table->string('email', 255)->nullable();
            $table->time('check_in_time')->default('15:00:00');
            $table->time('check_out_time')->default('11:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};