// database/migrations/2024_01_01_000006_create_cancel_policies_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancel_policies', function (Blueprint $table) {
            $table->uuid('cancel_policy_id')->primary();
            $table->uuid('facility_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('facility_id')->references('facility_id')->on('facilities');
        });

        Schema::create('cancel_policy_rules', function (Blueprint $table) {
            $table->uuid('rule_id')->primary();
            $table->uuid('cancel_policy_id');
            $table->integer('days_before');
            $table->decimal('charge_rate', 5, 2);
            $table->boolean('is_noshow')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreign('cancel_policy_id')->references('cancel_policy_id')->on('cancel_policies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancel_policy_rules');
        Schema::dropIfExists('cancel_policies');
    }
};