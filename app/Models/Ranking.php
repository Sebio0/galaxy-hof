<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Ranking extends Model
{
    use HasUlids;

    protected $fillable = [
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
        return $this->belongsTo(HallOfFame::class, 'hof_user_id');
    }
}
