<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $primaryKey = 'game_id';

    protected $fillable = [
        'game_type_id', 'provider_id', 'sub_provider_id', 'icon', 'game_name', 
        'game_code', 'uni_game_code', 'on_maintenance', 'remarks', 
        'min_bet', 'max_bet', 'pay_lines', 'info', 'rtp', 'schedule', 
        'release_date', 'on_maintenance', 'is_freespin'
    ];

    public function gameType()
    {
        return $this->belongsTo(GameType::class, 'game_type_id', 'game_type_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id', 'provider_id');
    }

    public function subProvider()
    {
        return $this->belongsTo(SubProvider::class, 'sub_provider_id', 'sub_provider_id');
    }
}   

 