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

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:SummaryReport');
    }

}