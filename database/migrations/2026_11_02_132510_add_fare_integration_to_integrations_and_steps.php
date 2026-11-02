<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An integration may also integrate the fare: changing service there charges only the difference up to the higher
 * fare. Like the accessibility features, it exists only once recorded, so the column starts false. Each step records
 * whether its fare was integrated with the previous one; past steps paid in full.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table): void {
            $table->boolean('fare_integrated')->default(false);
        });
        Schema::table('itinerary_steps', function (Blueprint $table): void {
            $table->boolean('fare_integrated')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itinerary_steps', function (Blueprint $table): void {
            $table->dropColumn('fare_integrated');
        });
        Schema::table('integrations', function (Blueprint $table): void {
            $table->dropColumn('fare_integrated');
        });
    }
};
