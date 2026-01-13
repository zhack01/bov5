<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $connection = 'bo_aggreagate'; 
    public $timestamps = false;
    protected $table = 'per_round';
    protected $primaryKey = 'round_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function provider()
    {
        return $this->belongsTo(SubProvider::class, 'provider_id', 'sub_provider_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id', 'player_id');
    }
}