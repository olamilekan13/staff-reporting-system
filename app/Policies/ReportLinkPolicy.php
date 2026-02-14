<?php

namespace App\Policies;

use App\Models\ReportLink;
use App\Models\User;

class ReportLinkPolicy
{
    /**
     * Only super admins can list report links.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only super admins can view a report link.
     */
    public function view(User $user, ReportLink $reportLink): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only super admins can create report links.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only super admins can update report links.
     */
    public function update(User $user, ReportLink $reportLink): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only super admins can delete report links.
     */
    public function delete(User $user, ReportLink $reportLink): bool
    {
        return $user->isSuperAdmin();
    }
}
