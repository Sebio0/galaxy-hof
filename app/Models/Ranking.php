<?php

namespace App\Models;

use Cache;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Ranking extends Model
{
    use HasUlids;

    protected $fillable = [
        'hof_id',
        'hof_user_id',
        'ranking_type_id',
        'value',
    ];
    public function fillable(array $fillable): array
    {
        return [
            'hof_user_id',
            'ranking_type_id',
            'value',
        ];
    }

    public function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    public function rankingType(): BelongsTo
    {
        return $this->belongsTo(RankingType::class, 'ranking_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(HofUser::class, 'hof_user_id');
    }

    public function hof(): BelongsTo
    {
        return $this->belongsTo(HallOfFame::class, 'hof_id');
    }


    /**
     * Get ranking type ID by its key_name.
     *
     * Caches all key_name => id mappings for performance.
     *
     * @param string $keyName
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
