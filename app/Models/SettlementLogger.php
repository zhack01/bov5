<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementLogger extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jira_ticket_id',
        'round_id_hash',
        'encrypted_round_id',
        'settle_type',
        'amount',
        'status',
        'user_id',     
        'approved_by',
        'approved_at',
        'round_id',
        'operator_id',
        'client_name',
        'created_from_ip',
    ];

    /**
     * Get the user who initiated the settlement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}