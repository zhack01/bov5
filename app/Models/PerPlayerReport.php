<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerPlayerReport extends Model
{
    protected $table = 'bo_aggreagate.per_player';
    public $timestamps = false; // optional
    protected $guarded = [];
}
