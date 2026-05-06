// database/migrations/2024_01_01_000012_create_group_reservations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_reservations', function (Blueprint $table) {
            $table->uuid('group_reservation_id')->primary();
            $table->string('group_reservation_no', 20)->unique();
            $table->uuid('facility_id');
            $table->string('group_name', 200);
            $table->uuid('contact_guest_id');
            $table->enum('status', ['PENDING', 'CONFIRMED', 'PARTIAL_CANCELLED', 'CANCELLED'])->default('PENDING');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('total_rooms');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->foreign('contact_guest_id')->references('guest_id')->on('guests');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_reservations');
    }
};