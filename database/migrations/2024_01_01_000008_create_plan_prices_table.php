// database/migrations/2024_01_01_000008_create_plan_prices_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->uuid('plan_price_id')->primary();
            $table->uuid('plan_id');
            $table->uuid('room_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->set('day_of_week', ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'])
                  ->default('MON,TUE,WED,THU,FRI,SAT,SUN');
            $table->decimal('base_price', 10, 0);
            $table->decimal('adult_price', 10, 0)->default(0);
            $table->decimal('child_price', 10, 0)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('plan_id')->references('plan_id')->on('plans');
            $table->foreign('room_type_id')->references('room_type_id')->on('room_types');
            $table->index(['plan_id', 'start_date', 'end_date'], 'idx_plan_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};