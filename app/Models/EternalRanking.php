<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class EternalRanking extends Model
{
    use HasUlids;

    public $fillable = [
        'id',
        'round_number',
        'round_name'
    ];
}
