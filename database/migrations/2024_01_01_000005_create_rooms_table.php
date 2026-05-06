// database/migrations/2024_01_01_000005_create_rooms_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('room_id')->primary();
            $table->uuid('room_type_id');
            $table->uuid('facility_id');
            $table->string('room_number', 10);
            $table->integer('floor')->nullable();
            $table->enum('status', ['AVAILABLE', 'MAINTENANCE', 'CLOSED'])->default('AVAILABLE');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('room_type_id')->references('room_type_id')->on('room_types');
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->unique(['facility_id', 'room_number'], 'uq_facility_room_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};