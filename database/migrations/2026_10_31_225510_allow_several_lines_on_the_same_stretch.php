<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Several lines may serve the same stretch, so a connection is now unique by its stops, mode and line. Connections
 * without a line (taxi, app, bike and walking) stay unique by stretch and mode: PostgreSQL treats their empty line
 * as equal ("nulls not distinct"), which SQLite, used only by the tests, cannot do.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('connections', function (Blueprint $table): void {
            $table->dropUnique(['from_stop_id', 'to_stop_id', 'transport_mode_id']);
            $table->unique(['from_stop_id', 'to_stop_id', 'transport_mode_id', 'line_id'], 'connections_stops_mode_line_unique')
                ->nullsNotDistinct();
        });
    }

    /**
     * Reverse the migrations. It fails while two lines share a stretch, which the previous schema cannot hold.
     */
    public function down(): void
    {
        Schema::table('connections', function (Blueprint $table): void {
            $table->dropUnique('connections_stops_mode_line_unique');
            $table->unique(['from_stop_id', 'to_stop_id', 'transport_mode_id']);
        });
    }
};
