<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rede de transporte: modais, paradas, linhas, conexões, integrações e a versão
 * da rede. Valores em centavos, minutos e metros. As regras numéricas ficam em
 * CHECK, porque o `unsigned` do Laravel não tem efeito no PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('transport_modes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 40);
            $table->string('color', 7);
        });

        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('address', 200);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->foreignId('transport_mode_id')->index()->constrained()->restrictOnDelete();
            $table->boolean('accessible')->default(true);
            $table->smallInteger('bikes_available')->nullable();
            $table->smallInteger('docks_available')->nullable();
            $table->geography('location', subtype: 'point', srid: 4326)
                ->nullable()
                ->storedAs('ST_SetSRID(ST_MakePoint(longitude::double precision, latitude::double precision), 4326)::geography');
            $table->boolean('tactile_paving')->default(false);
            $table->boolean('visual_signs')->default(false);

            $table->spatialIndex('location', 'stops_location_gist');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE stops
                ADD CONSTRAINT stops_coordinates
                    CHECK (latitude BETWEEN -90 AND 90 AND longitude BETWEEN -180 AND 180),
                ADD CONSTRAINT stops_bike_counts
                    CHECK (bikes_available >= 0 AND docks_available >= 0)
        SQL);

        Schema::create('lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_mode_id')->index()->constrained()->restrictOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->time('starts_at', 0);
            $table->time('ends_at', 0);
            $table->smallInteger('frequency_minutes');
            $table->integer('fare_cents');

            // Alvo da chave composta de connections, que mantém o modal igual ao da linha.
            $table->unique(['id', 'transport_mode_id']);
        });

        DB::statement('ALTER TABLE lines ADD CONSTRAINT lines_valid CHECK (frequency_minutes > 0 AND fare_cents >= 0)');

        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_stop_id')->constrained('stops')->restrictOnDelete();
            $table->foreignId('to_stop_id')->index()->constrained('stops')->restrictOnDelete();
            $table->foreignId('transport_mode_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('line_id')->nullable()->constrained()->restrictOnDelete();
            $table->smallInteger('minutes');
            $table->integer('distance_meters');
            $table->integer('boarding_fare_cents')->nullable();
            $table->boolean('accessible')->default(true);
            $table->boolean('has_stairs')->default(false);
            $table->boolean('adapted')->default(true);
            $table->geometry('path', subtype: 'linestring', srid: 4326)->nullable();
            $table->boolean('audible_signals')->default(false);
            $table->boolean('visual_signs')->default(false);

            $table->foreign(['line_id', 'transport_mode_id'])
                ->references(['id', 'transport_mode_id'])->on('lines')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->index(['from_stop_id', 'transport_mode_id']);
            $table->index(['line_id', 'transport_mode_id']);
            $table->spatialIndex('path', 'connections_path_gist');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE connections
                -- Várias linhas podem atender o mesmo trecho; sem linha, um trecho por modal.
                ADD CONSTRAINT connections_stops_mode_line_unique
                    UNIQUE NULLS NOT DISTINCT (from_stop_id, to_stop_id, transport_mode_id, line_id),
                ADD CONSTRAINT connections_valid
                    CHECK (from_stop_id <> to_stop_id AND minutes > 0 AND distance_meters > 0 AND boarding_fare_cents >= 0),
                -- Com linha, cobra a tarifa da linha; sem linha, a própria.
                ADD CONSTRAINT connections_fare_source
                    CHECK ((line_id IS NULL) <> (boarding_fare_cents IS NULL))
        SQL);

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stop_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_mode_id')->index()->constrained('transport_modes')->restrictOnDelete();
            $table->foreignId('to_mode_id')->index()->constrained('transport_modes')->restrictOnDelete();
            $table->smallInteger('transfer_minutes');
            $table->string('description', 200);
            $table->boolean('accessible')->default(true);
            $table->boolean('fare_integrated')->default(false);

            $table->unique(['stop_id', 'from_mode_id', 'to_mode_id']);
        });

        DB::statement('ALTER TABLE integrations ADD CONSTRAINT integrations_valid CHECK (transfer_minutes >= 0)');

        Schema::create('network_versions', function (Blueprint $table) {
            $table->smallInteger('id')->primary();
            $table->string('version', 36);
        });

        DB::statement('ALTER TABLE network_versions ADD CONSTRAINT network_versions_single_row CHECK (id = 1)');
        DB::statement('INSERT INTO network_versions (id, version) VALUES (1, gen_random_uuid()::text)');
    }

    public function down(): void
    {
        Schema::dropIfExists('network_versions');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('connections');
        Schema::dropIfExists('lines');
        Schema::dropIfExists('stops');
        Schema::dropIfExists('transport_modes');
    }
};
