<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The route planner keeps the mobility network in the cache under a version that changes whenever the network does.
 * Triggers renew the version after every statement changing a network table, whoever runs it: the application, the
 * seeder or someone at the psql prompt. The version is random rather than a counter, so a rolled back change never
 * brings back a version already cached with other data.
 */
return new class extends Migration
{
    /**
     * Tables the route planner reads the network from.
     *
     * @var list<string>
     */
    private const array NETWORK_TABLES = ['transport_modes', 'stops', 'lines', 'connections', 'integrations'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('network_versions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('version', 36);
        });
        DB::table('network_versions')->insert(['id' => 1, 'version' => (string) Str::uuid()]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE network_versions ADD CONSTRAINT network_versions_single_row CHECK (id = 1)');
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION elomob_renew_network_version() RETURNS trigger AS $$
                BEGIN
                    UPDATE network_versions SET version = gen_random_uuid()::text;
                    RETURN NULL;
                END;
                $$ LANGUAGE plpgsql;
                SQL);
            foreach (self::NETWORK_TABLES as $table) {
                DB::statement("CREATE TRIGGER {$table}_renew_network_version AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON {$table} FOR EACH STATEMENT EXECUTE FUNCTION elomob_renew_network_version()");
            }

            return;
        }

        foreach ($this->sqliteTriggers() as $trigger => [$event, $table]) {
            DB::unprepared("CREATE TRIGGER {$trigger} AFTER {$event} ON {$table} BEGIN UPDATE network_versions SET version = lower(hex(randomblob(16))); END");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (self::NETWORK_TABLES as $table) {
                DB::statement("DROP TRIGGER {$table}_renew_network_version ON {$table}");
            }
            DB::statement('DROP FUNCTION elomob_renew_network_version()');
        } else {
            foreach (array_keys($this->sqliteTriggers()) as $trigger) {
                DB::unprepared("DROP TRIGGER {$trigger}");
            }
        }

        Schema::drop('network_versions');
    }

    /**
     * SQLite triggers run for each row and one event at a time, so each network table needs one per event. A later
     * migration that makes SQLite rebuild a network table drops its triggers, which MobilityNetworkTest would catch.
     *
     * @return array<string, array{string, string}>
     */
    private function sqliteTriggers(): array
    {
        $triggers = [];
        foreach (self::NETWORK_TABLES as $table) {
            foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
                $triggers[$table.'_'.strtolower($event).'_renews_network_version'] = [$event, $table];
            }
        }

        return $triggers;
    }
};
