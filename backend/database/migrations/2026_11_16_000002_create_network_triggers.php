<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gatilhos que mantêm connections.path alinhado às paradas e renovam a versão
 * da rede, usada como chave do cache do planejador.
 */
return new class extends Migration
{
    private const NETWORK_TABLES = ['transport_modes', 'stops', 'lines', 'connections', 'integrations'];

    public function up(): void
    {
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

            CREATE TRIGGER connections_sync_path
                BEFORE INSERT OR UPDATE OF from_stop_id, to_stop_id ON connections
                FOR EACH ROW EXECUTE FUNCTION elomob_sync_connection_path();

            CREATE TRIGGER stops_sync_connections
                AFTER UPDATE OF latitude, longitude ON stops
                FOR EACH ROW EXECUTE FUNCTION elomob_sync_stop_connections();

            CREATE OR REPLACE FUNCTION elomob_renew_network_version() RETURNS trigger AS $$
            BEGIN
                UPDATE network_versions SET version = gen_random_uuid()::text;
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;
            SQL); // marcador alinhado ao corpo: o PostgreSQL guarda as funções sem recuo extra

        foreach (self::NETWORK_TABLES as $table) {
            DB::unprepared(<<<SQL
                CREATE TRIGGER {$table}_renew_network_version
                    AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON {$table}
                    FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version();
            SQL);
        }
    }

    public function down(): void
    {
        foreach (self::NETWORK_TABLES as $table) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$table}_renew_network_version ON {$table}");
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS stops_sync_connections ON stops;
            DROP TRIGGER IF EXISTS connections_sync_path ON connections;
            DROP FUNCTION IF EXISTS elomob_renew_network_version();
            DROP FUNCTION IF EXISTS elomob_sync_stop_connections();
            DROP FUNCTION IF EXISTS elomob_sync_connection_path();
        SQL);
    }
};
