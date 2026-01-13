<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeGame extends Model
{
    protected $table = 'subscribe_games';
    protected $primaryKey = 'subs_id';
    protected $fillable = ['cgs_id', 'game_id', 'status_id'];
    public $timestamps = true;

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'game_id');
    }

    public function cgs()
    {
        // Links back to the Client Game Subscribe bridge table
        return $this->belongsTo(ClientGameSubscribe::class, 'cgs_id', 'cgs_id');
    }
}