<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    // Table name from your SQL
    protected $table = 'currencies';

    // Primary key
    protected $primaryKey = 'id';

    // Fields that can be filled
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'dec_code',
    ];
}