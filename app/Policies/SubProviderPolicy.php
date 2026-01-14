<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SubProvider;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubProviderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SubProvider');
    }

    public function view(AuthUser $authUser, SubProvider $subProvider): bool
    {
        return $authUser->can('View:SubProvider');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SubProvider');
    }

    public function update(AuthUser $authUser, SubProvider $subProvider): bool
    {
        return $authUser->can('Update:SubProvider');
    }

    public function delete(AuthUser $authUser, SubProvider $subProvider): bool
    {
        return $authUser->can('Delete:SubProvider');
    }

    public function restore(AuthUser $authUser, SubProvider $subProvider): bool
    {
        return $authUser->can('Restore:SubProvider');
    }

    public function forceDelete(AuthUser $authUser, SubProvider $subProvider): bool
    {
        return $authUser->can('ForceDelete:SubProvider');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SubProvider');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SubProvider');
    }

    public function replicate(AuthUser $authUser, SubProvider $subProvider): bool
    {
        return $authUser->can('Replicate:SubProvider');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SubProvider');
    }

}