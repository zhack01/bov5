<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerRound extends Model
{

    protected $connection = 'bo_aggreagate';
    protected $table = 'per_round';

    protected $primaryKey = 'round_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
