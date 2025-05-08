<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class ServerInstance extends Model
{
    use HasUlids;

    protected $fillable = [
        'server_name',
        'database_host',
        'database_name',
        'database_user',
        'database_password',
        'server_instance_id'
    ];

    protected $casts = [
        'database_host' => 'encrypted',
        'database_name' => 'encrypted',
        'database_user' => 'encrypted',
        'database_password' => 'encrypted',
    ];

    public function gameInstances(): HasMany|ServerInstance
    {
        return $this->hasMany(GameInstance::class, 'server_instance_id', 'server_instance_id');
    }
}
