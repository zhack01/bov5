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

    public function delete(AuthUser $authUser, GameReport $gameReport): bool
    {
        return $authUser->can('Delete:GameReport');
    }

    public function restore(AuthUser $authUser, GameReport $gameReport): bool
    {
        return $authUser->can('Restore:GameReport');
    }

    public function forceDelete(AuthUser $authUser, GameReport $gameReport): bool
    {
        return $authUser->can('ForceDelete:GameReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GameReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GameReport');
    }

    public function replicate(AuthUser $authUser, GameReport $gameReport): bool
    {
        return $authUser->can('Replicate:GameReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GameReport');
    }

}