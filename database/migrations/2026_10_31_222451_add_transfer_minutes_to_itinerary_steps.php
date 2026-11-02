<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The route planner now spends the transfer time of a registered integration when a trip changes service at its
 * stop. Each step records that time apart from its wait; both are part of the step's duration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('itinerary_steps', function (Blueprint $table): void {
            $table->unsignedSmallInteger('transfer_minutes')->default(0);
        });

        $this->replaceStepCheck('wait_minutes >= 0 AND transfer_minutes >= 0 AND wait_minutes + transfer_minutes <= minutes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replaceStepCheck('wait_minutes BETWEEN 0 AND minutes');

        Schema::table('itinerary_steps', function (Blueprint $table): void {
            $table->dropColumn('transfer_minutes');
        });
    }

    /**
     * Replace the PostgreSQL check of the step values, keeping its rules for position, duration, distance and cost.
     */
    private function replaceStepCheck(string $timeRule): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE itinerary_steps DROP CONSTRAINT itinerary_steps_valid');
        DB::statement("ALTER TABLE itinerary_steps ADD CONSTRAINT itinerary_steps_valid CHECK (sequence > 0 AND minutes > 0 AND distance_meters > 0 AND cost_cents >= 0 AND {$timeRule})");
    }
};
