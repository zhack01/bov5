<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameType extends Model
{
    // Explicitly define the table name
    protected $table = 'game_types';

    // Explicitly define the primary key
    protected $primaryKey = 'game_type_id';

    // If you ever want to link back to games
    public function games()
    {
        return $this->hasMany(Game::class, 'game_type_id', 'game_type_id');
    }
}