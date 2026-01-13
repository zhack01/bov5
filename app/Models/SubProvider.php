<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class SubProvider extends Model
{
    protected $table = 'sub_providers';
    protected $primaryKey = 'sub_provider_id';

    protected $fillable = [
        'provider_id',
        'prefix',
        'sub_provider_name',
        'icon',
        'on_maintenance',
        'free_round_status',
    ];

    /**
     * Automate saving to sub_provider_code table
     */
    protected static function booted()
    {
        // Handle Creation
        static::created(function ($subProvider) {
            DB::table('sub_provider_code')->insert([
                'sub_provider_id'   => $subProvider->sub_provider_id,
                'sub_provider_name' => $subProvider->sub_provider_name,
                'created_at'        => now(),
            ]);
        });

        // Handle Updates (only if name changed)
        static::updated(function ($subProvider) {
            // 'isDirty' checks if the name in the form is different from the name in the DB
            if ($subProvider->isDirty('sub_provider_name')) {
                DB::table('sub_provider_code')
                    ->where('sub_provider_id', $subProvider->sub_provider_id)
                    ->update([
                        'sub_provider_name' => $subProvider->sub_provider_name,
                    ]);
            }
        });
    }

    // If you ever want to link back to games
    public function games()
    {
        return $this->hasMany(Game::class, 'sub_provider_id', 'sub_provider_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id', 'provider_id');
    }

    public function subscriptions()
    {
        // This allows us to check if a provider is linked to any CGS record
        return $this->hasMany(SubscribeProvider::class, 'provider_id', 'sub_provider_id');
    }
}
