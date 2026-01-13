<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'client_id';

    protected $fillable = [
        'operator_id',
        'brand_id',
        'client_name',
        'default_currency',
        'api_ver',
        'status_id', 
        'player_details_url',
        'fund_transfer_url',
        'transaction_checker_url',
        'balance_url', 
        'debit_credit_transfer_url', 
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'operator_id');
    }

    public function players()
    {
        return $this->hasMany(Player::class, 'client_id', 'client_id');
    }

    public function oauthClient()
    {
        return $this->hasOne(OAuthClients::class, 'client_id', 'client_id');
    }

    public function subscription()
    {
        return $this->hasOne(ClientGameSubscribe::class, 'client_id', 'client_id');
    }
    public function hasGameAccess($gameId, $subProviderId): bool
    {
        // 1. Check if the client even has a subscription profile
        if (!$this->subscription) return false;

        // 2. Is the Provider subscribed?
        $providerActive = $this->subscription->subscribedProviders()
            ->where('provider_id', $subProviderId)
            ->where('status_id', 1)
            ->exists();

        if (!$providerActive) return false;

        // 3. Is there a specific block (Exclusion) on this game?
        $gameStatus = $this->subscription->subscribedGames()
            ->where('game_id', $gameId)
            ->first();

        // If no specific game row exists, they have access because the provider is active.
        // If a row exists, we follow the status_id (0 = blocked, 1 = allowed).
        return $gameStatus ? (bool)$gameStatus->status_id : true;
    }
}
