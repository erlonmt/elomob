<?php

namespace App\Models;

use App\Enums\TransportModeCode;
use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A directed edge of the mobility graph. A connection on a line takes the line's fare and mode (a composite
 * foreign key keeps the modes equal); only connections without a line store their own boarding fare.
 * On PostgreSQL the table also carries the PostGIS column "path" (kept in sync by triggers), which is
 * hidden so its binary representation never reaches JSON.
 *
 * @property string|null $path_geojson GeoJSON of the PostGIS path, set only by the withPathGeoJson scope.
 */
#[Fillable(['from_stop_id', 'to_stop_id', 'transport_mode_id', 'line_id', 'minutes', 'distance_meters', 'boarding_fare_cents', 'accessible', 'has_stairs', 'adapted', 'audible_signals', 'visual_signs'])]
#[Hidden(['path'])]
#[WithoutTimestamps]
class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_stop_id' => 'integer',
            'to_stop_id' => 'integer',
            'transport_mode_id' => 'integer',
            'line_id' => 'integer',
            'minutes' => 'integer',
            'distance_meters' => 'integer',
            'boarding_fare_cents' => 'integer',
            'accessible' => 'boolean',
            'has_stairs' => 'boolean',
            'adapted' => 'boolean',
            'audible_signals' => 'boolean',
            'visual_signs' => 'boolean',
        ];
    }

    /**
     * Get the stop where the connection starts.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function fromStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'from_stop_id');
    }

    /**
     * Get the stop where the connection ends.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function toStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'to_stop_id');
    }

    /**
     * Get the transport mode used along the connection.
     *
     * @return BelongsTo<TransportMode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'transport_mode_id');
    }

    /**
     * Get the scheduled line serving the connection, when there is one.
     *
     * @return BelongsTo<Line, $this>
     */
    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    /**
     * Get the fare charged when boarding: the line's fare for a scheduled service, otherwise the connection's
     * own fare (taxi, app, bike and walking). Connections on a line need their line relation.
     */
    public function fareCents(): int
    {
        return $this->line_id === null ? $this->boarding_fare_cents : $this->line->fare_cents;
    }

    /**
     * Get the service a passenger rides on this connection: its line; for a taxi or app ride, the ride itself, hired
     * from stop to stop; otherwise its mode. Needs the mode relation.
     */
    public function service(): string
    {
        $mode = TransportModeCode::from($this->mode->code);

        return match (true) {
            $this->line_id !== null => 'line:'.$this->line_id,
            $mode->isHiredPerRide() => $mode->value.':'.$this->id,
            default => $mode->value,
        };
    }

    /**
     * Whether a passenger going on to the given connection stays on board: the same line or the same bike. A taxi or
     * app ride ends at its stop, and walking has no vehicle to stay on. Both connections need their mode relation.
     */
    public function continuesOnBoard(Connection $next): bool
    {
        return $this->service() === $next->service() && TransportModeCode::from($next->mode->code)->boardsVehicle();
    }

    /**
     * Also select the PostGIS path as GeoJSON when the database supports it.
     *
     * @param  Builder<Connection>  $query
     */
    #[Scope]
    protected function withPathGeoJson(Builder $query): void
    {
        if ($query->getModel()->getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $query->select($query->qualifyColumn('*'))->selectRaw('ST_AsGeoJSON(path) AS path_geojson');
    }

    /**
     * Get the connection geometry: the given PostGIS path GeoJSON when there is one, otherwise a straight
     * segment between both stops (requires the fromStop and toStop relations).
     *
     * @return array{type: string, coordinates: list<array{float, float}>}
     */
    public function geometry(?string $pathGeoJson = null): array
    {
        if ($pathGeoJson !== null) {
            return json_decode($pathGeoJson, true, 512, JSON_THROW_ON_ERROR);
        }

        return [
            'type' => 'LineString',
            'coordinates' => [
                [$this->fromStop->longitude, $this->fromStop->latitude],
                [$this->toStop->longitude, $this->toStop->latitude],
            ],
        ];
    }
}
