// database/migrations/2024_01_01_000014_create_reservation_details_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_details', function (Blueprint $table) {
            $table->uuid('detail_id')->primary();
            $table->uuid('reservation_id');
            $table->uuid('room_id')->nullable();
            $table->uuid('room_type_id');
            $table->uuid('plan_id');
            $table->date('night_date');
            $table->decimal('daily_amount', 10, 0);
            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('reservation_id')->references('reservation_id')->on('reservations');
            $table->foreign('room_id')->references('room_id')->on('rooms');
            $table->foreign('room_type_id')->references('room_type_id')->on('room_types');
            $table->foreign('plan_id')->references('plan_id')->on('plans');
            $table->index('reservation_id', 'idx_reservation_id');
            $table->index('night_date', 'idx_night_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_details');
    }
};