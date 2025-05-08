<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class HofUser extends Model
{
    use HasUlids;

    protected $fillable = [
        'hof_id',
        'nickname',
        'coordinates',
        'alliance_tag',
    ];

    public function rankings(): HasMany|HofUser
    {
        return $this->hasMany(Ranking::class, 'hof_user_id');
    }
}
