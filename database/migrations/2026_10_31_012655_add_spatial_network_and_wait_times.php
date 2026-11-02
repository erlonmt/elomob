<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('itinerary_steps', function (Blueprint $table) {
            $table->unsignedSmallInteger('wait_minutes')->default(0);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('ALTER TABLE stops ADD COLUMN location geography(Point, 4326) GENERATED ALWAYS AS (ST_SetSRID(ST_MakePoint(longitude::double precision, latitude::double precision), 4326)::geography) STORED');
        DB::statement('CREATE INDEX stops_location_gist ON stops USING GIST (location)');
        DB::statement('ALTER TABLE connections ADD COLUMN path geometry(LineString, 4326)');
        DB::statement('CREATE INDEX connections_path_gist ON connections USING GIST (path)');
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION elomob_sync_connection_path() RETURNS trigger AS $$
            BEGIN
                SELECT ST_MakeLine(origin.location::geometry, destination.location::geometry)
                INTO NEW.path
                FROM stops origin, stops destination
                WHERE origin.id = NEW.from_stop_id AND destination.id = NEW.to_stop_id;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER connections_sync_path
            BEFORE INSERT OR UPDATE OF from_stop_id, to_stop_id ON connections
            FOR EACH ROW EXECUTE FUNCTION elomob_sync_connection_path();

            CREATE OR REPLACE FUNCTION elomob_sync_stop_connections() RETURNS trigger AS $$
            BEGIN
                UPDATE connections
                SET path = ST_MakeLine(origin.location::geometry, destination.location::geometry)
                FROM stops origin, stops destination
                WHERE origin.id = connections.from_stop_id
                  AND destination.id = connections.to_stop_id
                  AND (connections.from_stop_id = NEW.id OR connections.to_stop_id = NEW.id);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER stops_sync_connections
            AFTER UPDATE OF latitude, longitude ON stops
            FOR EACH ROW EXECUTE FUNCTION elomob_sync_stop_connections();

            UPDATE connections
            SET path = ST_MakeLine(origin.location::geometry, destination.location::geometry)
            FROM stops origin, stops destination
            WHERE origin.id = connections.from_stop_id AND destination.id = connections.to_stop_id;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER stops_sync_connections ON stops');
            DB::statement('DROP TRIGGER connections_sync_path ON connections');
            DB::statement('DROP FUNCTION elomob_sync_stop_connections()');
            DB::statement('DROP FUNCTION elomob_sync_connection_path()');
            DB::statement('ALTER TABLE connections DROP COLUMN path');
            DB::statement('ALTER TABLE stops DROP COLUMN location');
        }

        Schema::table('itinerary_steps', function (Blueprint $table) {
            $table->dropColumn('wait_minutes');
        });
    }
};
