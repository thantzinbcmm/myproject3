// database/migrations/2024_01_01_000013_create_reservations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('reservation_id')->primary();
            $table->string('reservation_no', 20)->unique();
            $table->uuid('facility_id');
            $table->uuid('group_reservation_id')->nullable();
            $table->uuid('guest_id');
            $table->uuid('member_id')->nullable();
            $table->enum('channel', ['DIRECT', 'PHONE', 'RAKUTEN', 'JALAN', 'AGENCY', 'CORPORATE', 'OTHER'])->default('DIRECT');
            $table->string('channel_reservation_no', 100)->nullable();
            $table->enum('status', ['PENDING', 'CONFIRMED', 'CHECKIN', 'CHECKOUT', 'CANCELLED', 'NOSHOW'])->default('PENDING');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('nights');
            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);
            $table->decimal('total_amount', 12, 0)->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->decimal('cancel_fee', 12, 0)->nullable()->default(0);
            $table->text('cancel_reason')->nullable();
            $table->string('cancel_policy_applied', 100)->nullable();
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checkin_at')->nullable();
            $table->timestamp('checkout_at')->nullable();
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->foreign('guest_id')->references('guest_id')->on('guests');
            $table->foreign('member_id')->references('member_id')->on('members');
            $table->foreign('group_reservation_id')->references('group_reservation_id')->on('group_reservations');
            $table->index(['facility_id', 'status'], 'idx_facility_status');
            $table->index('check_in_date', 'idx_check_in_date');
            $table->index('guest_id', 'idx_guest_id');
            $table->index('reservation_no', 'idx_reservation_no');
            $table->index(['status', 'check_in_date', 'check_out_date'], 'idx_status_dates');
            $table->index(['facility_id', 'status', 'check_in_date', 'check_out_date'], 'idx_reservation_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};