<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class HallOfFame extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'instance_round_id',
    ];

    public function fillable(array $fillable): array
    {
        return [
            'name',
            'instance_round_id',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(InstanceRound::class, 'instance_round_id');
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class, 'hof_id');
    }
}
