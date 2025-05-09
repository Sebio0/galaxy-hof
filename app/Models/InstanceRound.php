<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class InstanceRound extends Model
{
    use HasUlids;
    protected $fillable = [
        'name',
        'game_instance_id',
        'start_date',
        'end_date',
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function fillable(array $fillable): array
    {
        return[
            'name',
            'game_instance_id',
            'start_date',
            'end_date',
        ];
    }

    public function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(GameInstance::class, 'game_instance_id');
    }

    public function hofs(): HasMany|InstanceRound
    {
        return $this->hasMany(HallOfFame::class, 'instance_round_id');
    }
}
