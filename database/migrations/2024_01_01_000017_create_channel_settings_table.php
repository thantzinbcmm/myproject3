// database/migrations/2024_01_01_000017_create_channel_settings_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_settings', function (Blueprint $table) {
            $table->uuid('channel_setting_id')->primary();
            $table->uuid('facility_id');
            $table->string('channel_code', 20);
            $table->string('channel_name', 100);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->unique(['facility_id', 'channel_code'], 'uq_facility_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_settings');
    }
};