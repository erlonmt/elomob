<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('preferred_route', 20)->default('fastest');
            $t->boolean('accessible_only')->default(false);
            $t->boolean('avoid_stairs')->default(false);
            $t->boolean('adapted_only')->default(false);
            $t->json('transport_modes')->nullable();
        });
        Schema::create('transport_modes', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 40);
            $t->string('color', 7);
        });
        Schema::create('stops', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120)->unique();
            $t->string('address', 200);
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->foreignId('transport_mode_id')->constrained()->restrictOnDelete();
            $t->boolean('accessible')->default(true);
            $t->unsignedSmallInteger('bikes_available')->nullable();
            $t->unsignedSmallInteger('docks_available')->nullable();
        });
        Schema::create('lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transport_mode_id')->constrained()->restrictOnDelete();
            $t->string('code', 20)->unique();
            $t->string('name', 150);
            $t->time('starts_at');
            $t->time('ends_at');
            $t->unsignedSmallInteger('frequency_minutes');
            $t->unsignedInteger('fare_cents');
        });
        Schema::create('connections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('from_stop_id')->constrained('stops')->restrictOnDelete();
            $t->foreignId('to_stop_id')->constrained('stops')->restrictOnDelete();
            $t->foreignId('transport_mode_id')->constrained()->restrictOnDelete();
            $t->foreignId('line_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('minutes');
            $t->unsignedInteger('distance_meters');
            $t->unsignedInteger('boarding_fare_cents')->default(0);
            $t->boolean('accessible')->default(true);
            $t->boolean('has_stairs')->default(false);
            $t->boolean('adapted')->default(true);
            $t->index(['from_stop_id', 'transport_mode_id']);
            $t->unique(['from_stop_id', 'to_stop_id', 'transport_mode_id']);
        });
        Schema::create('integrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stop_id')->constrained()->restrictOnDelete();
            $t->foreignId('from_mode_id')->constrained('transport_modes')->restrictOnDelete();
            $t->foreignId('to_mode_id')->constrained('transport_modes')->restrictOnDelete();
            $t->unsignedSmallInteger('transfer_minutes');
            $t->string('description', 200);
            $t->boolean('accessible')->default(true);
            $t->unique(['stop_id', 'from_mode_id', 'to_mode_id']);
        });
        Schema::create('saved_places', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('stop_id')->constrained()->restrictOnDelete();
            $t->string('label', 60);
            $t->timestamps();
            $t->unique(['user_id', 'label']);
        });
        Schema::create('trip_searches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('session_hash', 64)->index();
            $t->foreignId('origin_id')->constrained('stops')->restrictOnDelete();
            $t->foreignId('destination_id')->constrained('stops')->restrictOnDelete();
            $t->json('filters');
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
        Schema::create('itineraries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('trip_search_id')->constrained()->cascadeOnDelete();
            $t->string('criterion', 25);
            $t->unsignedInteger('total_minutes');
            $t->unsignedInteger('total_cost_cents');
            $t->unsignedInteger('total_distance_meters');
            $t->unsignedSmallInteger('transfers');
            $t->unique(['trip_search_id', 'criterion']);
        });
        Schema::create('itinerary_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $t->foreignId('connection_id')->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('sequence');
            $t->unsignedSmallInteger('minutes');
            $t->unsignedInteger('cost_cents');
            $t->unsignedInteger('distance_meters');
            $t->unique(['itinerary_id', 'sequence']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE connections ADD CONSTRAINT connections_valid CHECK (from_stop_id <> to_stop_id AND minutes > 0 AND distance_meters > 0 AND boarding_fare_cents >= 0)');
            DB::statement('ALTER TABLE stops ADD CONSTRAINT stops_coordinates CHECK (latitude BETWEEN -90 AND 90 AND longitude BETWEEN -180 AND 180)');
            DB::statement('ALTER TABLE trip_searches ADD CONSTRAINT searches_distinct_places CHECK (origin_id <> destination_id)');
        }
    }

    public function down(): void
    {
        foreach (['itinerary_steps', 'itineraries', 'trip_searches', 'saved_places', 'integrations', 'connections', 'lines', 'stops', 'transport_modes'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['preferred_route', 'accessible_only', 'avoid_stairs', 'adapted_only', 'transport_modes']));
    }
};
