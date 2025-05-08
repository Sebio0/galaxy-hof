<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class RankingType extends Model
{
    use HasUlids;

    protected $fillable = [
        'key_name',
        'display_name',
        'type',
    ];
}
