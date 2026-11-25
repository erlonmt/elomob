<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Busca de trajeto. Buscas sem conta são apagadas uma semana depois pela
 * limpeza diária (`model:prune`); as de cada conta ficam no histórico.
 */
class TripSearch extends Model
{
    use Prunable;

    public const GUEST_RETENTION_DAYS = 7;

    protected $fillable = ['user_id', 'session_hash', 'origin_id', 'destination_id', 'filters', 'departure_at'];

    protected $hidden = ['session_hash'];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'departure_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'origin_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'destination_id');
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }

    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('user_id')
            ->where('created_at', '<', now()->subDays(self::GUEST_RETENTION_DAYS));
    }

    /** Hash guardado no banco para o token entregue ao visitante. */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
