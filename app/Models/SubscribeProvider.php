<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeProvider extends Model
{
    protected $table = 'subscribe_provider';
    protected $primaryKey = 'subs_id';
    protected $fillable = ['cgs_id', 'provider_id', 'status_id', 'is_uni_game_code'];


    public function cgs()
    {
        return $this->belongsTo(ClientGameSubscribe::class, 'cgs_id', 'cgs_id');
    }

    public function subProvider()
    {
        return $this->belongsTo(SubProvider::class, 'provider_id', 'sub_provider_id');
    }
}