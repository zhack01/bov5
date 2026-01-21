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

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:Operator');
    }

}