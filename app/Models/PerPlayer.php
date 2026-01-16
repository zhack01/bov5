<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerPlayer extends Model
{
    protected $table = 'bo_aggreagate.per_player';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Force read-only (safety)
     */
    protected static function booted()
    {
       
    }
}
