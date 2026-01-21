<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Game;
use Illuminate\Auth\Access\HandlesAuthorization;

class GamePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Game');
    }

    public function view(AuthUser $authUser, Game $game): bool
    {
        return $authUser->can('View:Game');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Game');
    }

    public function update(AuthUser $authUser, Game $game): bool
    {
        return $authUser->can('Update:Game');
    }

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:Game');
    }

}