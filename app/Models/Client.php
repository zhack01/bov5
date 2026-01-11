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
        'client_id',
        'default_currency',
        'api_ver',
        'player_details_url',
        'fund_transfer_url',
        'transaction_checker_url',
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
}