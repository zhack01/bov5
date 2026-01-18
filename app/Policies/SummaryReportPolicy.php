<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SummaryReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class SummaryReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SummaryReport');
    }

    public function view(AuthUser $authUser, SummaryReport $summaryReport): bool
    {
        return $authUser->can('View:SummaryReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SummaryReport');
    }

    public function update(AuthUser $authUser, SummaryReport $summaryReport): bool
    {
        return $authUser->can('Update:SummaryReport');
    }

    public function delete(AuthUser $authUser, SummaryReport $summaryReport): bool
    {
        return $authUser->can('Delete:SummaryReport');
    }

    public function restore(AuthUser $authUser, SummaryReport $summaryReport): bool
    {
        return $authUser->can('Restore:SummaryReport');
    }

    public function forceDelete(AuthUser $authUser, SummaryReport $summaryReport): bool
    {
        return $authUser->can('ForceDelete:SummaryReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SummaryReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SummaryReport');
    }

    public function replicate(AuthUser $authUser, SummaryReport $summaryReport): bool
    {
        return $authUser->can('Replicate:SummaryReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SummaryReport');
    }

}