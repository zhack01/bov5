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

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:SubProvider');
    }

}