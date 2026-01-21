<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CurrencyRate;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurrencyRatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CurrencyRate');
    }

    public function view(AuthUser $authUser, CurrencyRate $currencyRate): bool
    {
        return $authUser->can('View:CurrencyRate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CurrencyRate');
    }

    public function update(AuthUser $authUser, CurrencyRate $currencyRate): bool
    {
        return $authUser->can('Update:CurrencyRate');
    }

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:CurrencyRate');
    }

}