<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A connection on a line repeated the line's fare and mode, and nothing kept them equal. From now on it takes
 * its fare from the line ("boarding_fare_cents" is only stored for connections without a line: taxi, app, bike
 * and walking), and a composite foreign key keeps its mode equal to the line's, following any change of it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lines', function (Blueprint $table): void {
            $table->unique(['id', 'transport_mode_id']);
        });

        Schema::table('connections', function (Blueprint $table): void {
            $table->unsignedInteger('boarding_fare_cents')->nullable()->change();
            $table->dropIndex(['line_id']);
            $table->index(['line_id', 'transport_mode_id']);
            $table->foreign(['line_id', 'transport_mode_id'])
                ->references(['id', 'transport_mode_id'])->on('lines')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        DB::table('connections')->whereNotNull('line_id')->update(['boarding_fare_cents' => null]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE connections ADD CONSTRAINT connections_fare_source CHECK ((line_id IS NULL) <> (boarding_fare_cents IS NULL))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE connections DROP CONSTRAINT connections_fare_source');
        }

        DB::table('connections')->whereNotNull('line_id')->update([
            'boarding_fare_cents' => DB::raw('(SELECT fare_cents FROM lines WHERE lines.id = connections.line_id)'),
        ]);

        Schema::table('connections', function (Blueprint $table): void {
            $table->dropForeign(['line_id', 'transport_mode_id']);
            $table->dropIndex(['line_id', 'transport_mode_id']);
            $table->index('line_id');
            $table->unsignedInteger('boarding_fare_cents')->default(0)->change();
        });

        Schema::table('lines', function (Blueprint $table): void {
            $table->dropUnique(['id', 'transport_mode_id']);
        });
    }
};
