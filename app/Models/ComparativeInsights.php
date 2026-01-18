<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComparativeInsights extends Model
{
    protected $connection = 'bo_aggreagate';
    protected $table = 'per_player';
    
    // Use a REAL column here so the auto-generated ORDER BY works
    protected $primaryKey = 'per_player_id';
    public $incrementing = false;
    protected $keyType = 'string';

     // VERY IMPORTANT 👇
     public $timestamps = false;

    // Auto-load names to avoid empty columns
    // protected $with = ['operators', 'clients', 'providers', 'games'];
    
    public function operators() { return $this->belongsTo(Operator::class, 'operator_id', 'operator_id'); }
    public function clients() { return $this->belongsTo(Client::class, 'client_id', 'client_id'); }
    public function providers() { return $this->belongsTo(SubProvider::class, 'provider_id', 'sub_provider_id'); }
    public function games() { return $this->belongsTo(Game::class, 'game_id', 'game_id'); }
    public function players() { return $this->belongsTo(Player::class, 'player_id', 'player_id'); }

    public function partners()
    {
        return $this->hasOneThrough(
            Provider::class,
            SubProvider::class,
            'sub_provider_id', 
            'provider_id', 
            'provider_id', 
            'provider_id'
        );
    }
}
