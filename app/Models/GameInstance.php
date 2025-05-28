<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameInstance extends Model
{
    use SoftDeletes;
    use HasUlids;


    protected $fillable = ['name', 'id', 'server_instance_id', 'primary'];

    public function rounds(): GameInstance|HasMany
    {
        return $this->hasMany(\App\Models\InstanceRound::class);
    }

    public function serverInstance(): BelongsTo
    {
        return $this->belongsTo(ServerInstance::class, 'server_instance_id', 'id');
    }
}
