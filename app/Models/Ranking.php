<?php

namespace App\Models;

use Cache;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hof_id',
        'hof_user_id',
        'ranking_type_id',
        'value',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $casts = [
        'value' => 'integer',
    ];

    /**
     * Relationship to the Hall of Fame instance.
     */
    public function hof(): BelongsTo
    {
        return $this->belongsTo(HallOfFame::class, 'hof_id');
    }

    /**
     * Relationship to the user in the Hall of Fame.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(HofUser::class, 'hof_user_id');
    }

    /**
     * Relationship to the ranking type.
     */
    public function rankingType(): BelongsTo
    {
        return $this->belongsTo(RankingType::class, 'ranking_type_id');
    }

    /**
     * Get ranking type ID by its key name, cached for performance.
     *
     * @param string \$keyName
     * @return string|null
     */
    public static function typeIdByKeyName(string $keyName): ?string
    {
        $cacheKey = 'ranking_types:key_to_id';

        $map = Cache::rememberForever($cacheKey, function () {
        return \DB::table('ranking_types')
            ->pluck('id', 'key_name')
            ->toArray();
    });

        return $map[$keyName] ?? null;
    }
}
