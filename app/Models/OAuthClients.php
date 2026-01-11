<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthClients extends Model
{
    protected $table = 'oauth_clients';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [ 'client_id', 'client_secret' ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

}
