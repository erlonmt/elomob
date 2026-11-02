<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accessibility moves from three mobility switches to the kinds of disability a person declares: wheelchair,
 * reduced mobility, visual and hearing. Stops gain tactile paving and visual signs, connections gain audible and
 * visual signs; a feature exists only once recorded, so the new columns start false. Accounts and the options
 * stored with past searches are converted: "accessible_only" or "adapted_only" become a wheelchair need and
 * "avoid_stairs" a reduced mobility need.
 */
return new class extends Migration
{
    /**
     * Codes of the accessibility needs, in the order the application lists them.
     *
     * @var list<string>
     */
    private const array NEEDS = ['wheelchair', 'reduced_mobility', 'visual', 'hearing'];

    /**
     * The accessibility switches the needs replace.
     *
     * @var list<string>
     */
    private const array SWITCHES = ['accessible_only', 'avoid_stairs', 'adapted_only'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stops', function (Blueprint $table): void {
            $table->boolean('tactile_paving')->default(false);
            $table->boolean('visual_signs')->default(false);
        });
        Schema::table('connections', function (Blueprint $table): void {
            $table->boolean('audible_signals')->default(false);
            $table->boolean('visual_signs')->default(false);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->json('accessibility_needs')->default('[]');
        });

        DB::table('users')->select(['id', ...self::SWITCHES])->lazyById()->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'accessibility_needs' => json_encode($this->needsFrom((array) $user), JSON_THROW_ON_ERROR),
            ]);
        });
        $this->convertSearchFilters(function (array $filters): array {
            $needs = $this->needsFrom($filters);

            return array_diff_key($filters, array_flip(self::SWITCHES)) + ['accessibility_needs' => $needs];
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(self::SWITCHES);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN accessibility_needs DROP DEFAULT');
            DB::statement('ALTER TABLE users ALTER COLUMN accessibility_needs TYPE jsonb USING accessibility_needs::jsonb');
            DB::statement("ALTER TABLE users ALTER COLUMN accessibility_needs SET DEFAULT '[]'::jsonb");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_accessibility_needs CHECK (jsonb_typeof(accessibility_needs) = 'array' AND accessibility_needs <@ '".json_encode(self::NEEDS)."'::jsonb)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT users_accessibility_needs');
        }

        Schema::table('users', function (Blueprint $table): void {
            foreach (self::SWITCHES as $switch) {
                $table->boolean($switch)->default(false);
            }
        });
        DB::table('users')->select(['id', 'accessibility_needs'])->lazyById()->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(
                $this->switchesFrom(json_decode($user->accessibility_needs, true, 512, JSON_THROW_ON_ERROR)),
            );
        });
        $this->convertSearchFilters(function (array $filters): array {
            $switches = $this->switchesFrom($filters['accessibility_needs'] ?? []);
            unset($filters['accessibility_needs']);

            return $filters + $switches;
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('accessibility_needs');
        });
        Schema::table('connections', function (Blueprint $table): void {
            $table->dropColumn(['audible_signals', 'visual_signs']);
        });
        Schema::table('stops', function (Blueprint $table): void {
            $table->dropColumn(['tactile_paving', 'visual_signs']);
        });
    }

    /**
     * Rewrite the options stored with every search.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $convert
     */
    private function convertSearchFilters(callable $convert): void
    {
        DB::table('trip_searches')->select(['id', 'filters'])->lazyById()->each(function (object $search) use ($convert): void {
            $filters = $convert(json_decode($search->filters, true, 512, JSON_THROW_ON_ERROR));

            DB::table('trip_searches')->where('id', $search->id)->update(['filters' => json_encode($filters, JSON_THROW_ON_ERROR)]);
        });
    }

    /**
     * Needs matching the switches that are on.
     *
     * @param  array<string, mixed>  $switches
     * @return list<string>
     */
    private function needsFrom(array $switches): array
    {
        $needs = [];
        if (($switches['accessible_only'] ?? false) || ($switches['adapted_only'] ?? false)) {
            $needs[] = 'wheelchair';
        }
        if ($switches['avoid_stairs'] ?? false) {
            $needs[] = 'reduced_mobility';
        }

        return $needs;
    }

    /**
     * Switches covering the given needs: a wheelchair turns on all three, reduced mobility avoids stairs. Visual and
     * hearing needs have no switch and are lost.
     *
     * @param  list<string>  $needs
     * @return array{accessible_only: bool, avoid_stairs: bool, adapted_only: bool}
     */
    private function switchesFrom(array $needs): array
    {
        $wheelchair = in_array('wheelchair', $needs, true);

        return [
            'accessible_only' => $wheelchair,
            'avoid_stairs' => $wheelchair || in_array('reduced_mobility', $needs, true),
            'adapted_only' => $wheelchair,
        ];
    }
};
