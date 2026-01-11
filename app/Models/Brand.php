<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $primaryKey = 'brand_id';

    protected $fillable = ['brand_name', 'operator_id', 'status_id'];

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'operator_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'brand_id', 'brand_id');
    }

    protected $casts = [
        'selected_currencies' => 'array',
    ];

    protected static function booted()
    {
        
    }
}