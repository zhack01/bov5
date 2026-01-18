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

    public function delete(AuthUser $authUser, CurrencyRate $currencyRate): bool
    {
        return $authUser->can('Delete:CurrencyRate');
    }

    public function restore(AuthUser $authUser, CurrencyRate $currencyRate): bool
    {
        return $authUser->can('Restore:CurrencyRate');
    }

    public function forceDelete(AuthUser $authUser, CurrencyRate $currencyRate): bool
    {
        return $authUser->can('ForceDelete:CurrencyRate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CurrencyRate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CurrencyRate');
    }

    public function replicate(AuthUser $authUser, CurrencyRate $currencyRate): bool
    {
        return $authUser->can('Replicate:CurrencyRate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CurrencyRate');
    }

}