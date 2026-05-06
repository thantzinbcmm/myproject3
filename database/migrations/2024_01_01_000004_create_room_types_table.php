// database/migrations/2024_01_01_000004_create_room_types_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->uuid('room_type_id')->primary();
            $table->uuid('facility_id');
            $table->string('type_code', 20);
            $table->string('name_ja', 100);
            $table->string('name_en', 100);
            $table->string('name_zh_cn', 100)->nullable();
            $table->string('name_zh_tw', 100)->nullable();
            $table->string('name_ko', 100)->nullable();
            $table->string('name_my', 100)->nullable();
            $table->text('description_ja')->nullable();
            $table->text('description_en')->nullable();
            $table->integer('standard_capacity');
            $table->integer('max_capacity');
            $table->decimal('floor_area', 6, 2)->nullable();
            $table->json('amenities')->nullable();
            $table->json('image_urls')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->unique(['facility_id', 'type_code'], 'uq_facility_type_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};