<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An itinerary may also be the public transport option: the fastest trip only by bus and metro, offered when no other
 * criterion chose one. A profile still prefers one of the four criteria, so users_preferred_route stays as it is.
 * Rolling back deletes those itineraries, which the former constraint would refuse.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE itineraries DROP CONSTRAINT itineraries_criterion');
        DB::statement("ALTER TABLE itineraries ADD CONSTRAINT itineraries_criterion CHECK (criterion IN ('fastest', 'cheapest', 'transfers', 'sustainable', 'public_transport'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('itineraries')->where('criterion', 'public_transport')->delete();
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE itineraries DROP CONSTRAINT itineraries_criterion');
        DB::statement("ALTER TABLE itineraries ADD CONSTRAINT itineraries_criterion CHECK (criterion IN ('fastest', 'cheapest', 'transfers', 'sustainable'))");
    }
};
