// database/migrations/2024_01_01_000009_create_inventory_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->uuid('inventory_id')->primary();
            $table->uuid('room_type_id');
            $table->uuid('facility_id');
            $table->date('date');
            $table->integer('total_count')->default(0);
            $table->integer('booked_count')->default(0);
            $table->integer('closed_count')->default(0);
            $table->boolean('stop_sale')->default(false);
            $table->integer('version')->default(0);
            $table->timestamps();
            $table->foreign('room_type_id')->references('room_type_id')->on('room_types');
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->unique(['room_type_id', 'date'], 'uq_room_type_date');
            $table->index(['facility_id', 'date'], 'idx_facility_date');
            $table->index(['facility_id', 'date', 'room_type_id'], 'idx_inventory_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};