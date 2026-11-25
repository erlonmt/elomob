<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Favoritos do usuário e buscas de trajeto com seus itinerários e etapas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stop_id')->index()->constrained()->restrictOnDelete();
            $table->string('label', 60);
            $table->timestamps();

            $table->unique(['user_id', 'label']);
        });

        // Sem conta, a busca fica ligada ao hash do token de visitante;
        // departure_at vazio significa sair agora.
        Schema::create('trip_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_hash', 64)->index();
            $table->foreignId('origin_id')->index()->constrained('stops')->restrictOnDelete();
            $table->foreignId('destination_id')->index()->constrained('stops')->restrictOnDelete();
            $table->jsonb('filters');
            $table->timestamps();
            $table->timestamp('departure_at')->nullable();

            $table->index(['user_id', 'created_at']);
        });

        DB::statement('ALTER TABLE trip_searches ADD CONSTRAINT searches_distinct_places CHECK (origin_id <> destination_id)');

        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_search_id')->constrained()->cascadeOnDelete();
            $table->string('criterion', 25);
            $table->integer('total_minutes');
            $table->integer('total_cost_cents');
            $table->integer('total_distance_meters');
            $table->smallInteger('transfers');

            $table->unique(['trip_search_id', 'criterion']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE itineraries
                ADD CONSTRAINT itineraries_criterion
                    CHECK (criterion IN ('fastest', 'cheapest', 'transfers', 'sustainable')),
                ADD CONSTRAINT itineraries_valid
                    CHECK (total_minutes > 0 AND total_distance_meters > 0 AND total_cost_cents >= 0 AND transfers >= 0)
        SQL);

        // `minutes` já inclui a espera e o tempo de integração.
        Schema::create('itinerary_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->index()->constrained()->restrictOnDelete();
            $table->smallInteger('sequence');
            $table->smallInteger('minutes');
            $table->integer('cost_cents');
            $table->integer('distance_meters');
            $table->smallInteger('wait_minutes')->default(0);
            $table->smallInteger('transfer_minutes')->default(0);
            $table->boolean('fare_integrated')->default(false);

            $table->unique(['itinerary_id', 'sequence']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE itinerary_steps
                ADD CONSTRAINT itinerary_steps_valid
                    CHECK (sequence > 0 AND minutes > 0 AND distance_meters > 0 AND cost_cents >= 0
                           AND wait_minutes >= 0 AND transfer_minutes >= 0
                           AND wait_minutes + transfer_minutes <= minutes)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_steps');
        Schema::dropIfExists('itineraries');
        Schema::dropIfExists('trip_searches');
        Schema::dropIfExists('saved_places');
    }
};
