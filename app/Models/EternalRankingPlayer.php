<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class EternalRankingPlayer extends Model
{
    use HasUlids;

    public $fillable = [
        'id',
        'email_hash',
        'nickname',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(EternalRankingResult::class, 'player_id');
    }
}
