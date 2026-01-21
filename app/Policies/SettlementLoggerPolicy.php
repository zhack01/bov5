<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SettlementLogger;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettlementLoggerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SettlementLogger');
    }

    public function view(AuthUser $authUser, SettlementLogger $settlementLogger): bool
    {
        return $authUser->can('View:SettlementLogger');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SettlementLogger');
    }

    public function update(AuthUser $authUser, SettlementLogger $settlementLogger): bool
    {
        return $authUser->can('Update:SettlementLogger');
    }

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:SettlementLogger');
    }

    public function reject(AuthUser $authUser): bool
    {
        return $authUser->can('Reject:SettlementLogger');
    }

}