<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GameReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GameReport');
    }

    public function view(AuthUser $authUser, GameReport $gameReport): bool
    {
        return $authUser->can('View:GameReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GameReport');
    }

    public function update(AuthUser $authUser, GameReport $gameReport): bool
    {
        return $authUser->can('Update:GameReport');
    }

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:GameReport');
    }

}