// database/migrations/2024_01_01_000007_create_plans_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('plan_id')->primary();
            $table->uuid('facility_id');
            $table->string('plan_code', 20);
            $table->string('name_ja', 200);
            $table->string('name_en', 200);
            $table->string('name_zh_cn', 200)->nullable();
            $table->string('name_zh_tw', 200)->nullable();
            $table->string('name_ko', 200)->nullable();
            $table->string('name_my', 200)->nullable();
            $table->text('description_ja')->nullable();
            $table->text('description_en')->nullable();
            $table->enum('meal_type', ['NONE', 'BREAKFAST', 'DINNER', 'HALF_BOARD', 'FULL_BOARD'])->default('NONE');
            $table->integer('min_nights')->default(1);
            $table->integer('max_nights')->nullable();
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->uuid('cancel_policy_id')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
            $table->foreign('cancel_policy_id')->references('cancel_policy_id')->on('cancel_policies');
            $table->unique(['facility_id', 'plan_code'], 'uq_facility_plan_code');
        });

        Schema::create('plan_room_types', function (Blueprint $table) {
            $table->uuid('plan_room_type_id')->primary();
            $table->uuid('plan_id');
            $table->uuid('room_type_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('plan_id')->references('plan_id')->on('plans');
            $table->foreign('room_type_id')->references('room_type_id')->on('room_types');
            $table->unique(['plan_id', 'room_type_id'], 'uq_plan_room_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_room_types');
        Schema::dropIfExists('plans');
    }
};