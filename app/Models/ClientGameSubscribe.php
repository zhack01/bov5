<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientGameSubscribe extends Model
{
    protected $table = 'client_game_subscribe';
    protected $primaryKey = 'cgs_id';
    protected $fillable = ['client_id'];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function subscribedProviders()
    {
        return $this->hasMany(SubscribeProvider::class, 'cgs_id', 'cgs_id');
    }

    public function subscribedGames()
    {
        return $this->hasMany(SubscribeGame::class, 'cgs_id', 'cgs_id');
    }
}