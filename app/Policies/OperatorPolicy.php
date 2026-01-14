<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Operator;
use Illuminate\Auth\Access\HandlesAuthorization;

class OperatorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Operator');
    }

    public function view(AuthUser $authUser, Operator $operator): bool
    {
        return $authUser->can('View:Operator');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Operator');
    }

    public function update(AuthUser $authUser, Operator $operator): bool
    {
        return $authUser->can('Update:Operator');
    }

    public function delete(AuthUser $authUser, Operator $operator): bool
    {
        return $authUser->can('Delete:Operator');
    }

    public function restore(AuthUser $authUser, Operator $operator): bool
    {
        return $authUser->can('Restore:Operator');
    }

    public function forceDelete(AuthUser $authUser, Operator $operator): bool
    {
        return $authUser->can('ForceDelete:Operator');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Operator');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Operator');
    }

    public function replicate(AuthUser $authUser, Operator $operator): bool
    {
        return $authUser->can('Replicate:Operator');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Operator');
    }

}