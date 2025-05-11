<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class EternalRankingResult extends Model
{
    use HasUlids;

    public $fillable = [
        'id',
        'ranking_id',
        'player_id',
        'score',
        'pct',
    ];

    public function ranking(): BelongsTo {
        return $this->belongsTo(EternalRanking::class, 'ranking_id');
    }
}
