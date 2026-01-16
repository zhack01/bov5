<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $connection = 'mysql'; 
    protected $table = 'operator';
    protected $primaryKey = 'operator_id';

    protected $fillable = [
        'client_name', 'client_code', 'client_api_key', 'client_access_token',
        'hashkey', 'status_id', 'db_conn', 'db_schema', 'multi_currency', 'wallet_type'
    ];

    protected $casts = [
        'multi_currency' => 'json',
    ];

    public function brands()
    {
        return $this->hasMany(Brand::class, 'operator_id', 'operator_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'operator_id', 'operator_id');
    }

    public function oauthClient()
    {
        // Operator -> Client -> OauthClient
        return $this->hasOneThrough(
            OAuthClients::class, 
            Client::class,
            'operator_id', // Foreign key on clients table
            'client_id',   // Foreign key on oauth_clients table
            'operator_id', // Local key on operators table
            'client_id'    // Local key on clients table
        );
    }
}