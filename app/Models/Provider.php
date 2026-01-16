<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $connection = 'mysql'; 
    protected $table = 'providers';
    protected $primaryKey = 'provider_id';

    // Add this section to fix the MassAssignmentException
    protected $fillable = [
        'provider_name',
        'icon',
        'languages',
        'currencies',
        'ext_parameter',
        'on_maintenance',
    ];

    // Ensure these are cast to arrays so Filament can save the JSON correctly
    protected $casts = [
        'languages' => 'array',
        'currencies' => 'array',
        'ext_parameter' => 'array',
        'on_maintenance' => 'boolean',
    ];

    public function subProvider()
    {
        return $this->hasOne(SubProvider::class, 'provider_id', 'provider_id');
    }

    // If you ever want to link back to games
    public function games()
    {
        return $this->hasMany(Game::class, 'provider_id', 'provider_id');
    }
}
