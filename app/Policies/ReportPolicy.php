<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * All authenticated users can list reports (filtered by their access level).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        // Owner can view their own
        if ($report->user_id === $user->id) {
            return true;
        }

        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Head of Operations can view all
        if ($user->hasRole('head_of_operations')) {
            return true;
        }

        // HOD can view department reports
        if ($user->isHOD() && $report->department_id === $user->department_id) {
            return true;
        }

        return false;
    }

    /**
     * All authenticated users can create reports (except super_admin only).
     */
    public function create(User $user): bool
    {
        // Super admin only accounts shouldn't create reports
        if ($user->isSuperAdmin() && !$user->hasRole('admin')) {
            return false;
        }

        return true;
    }

    /**
     * Owner can update if status is draft.
     */
    public function update(User $user, Report $report): bool
    {
        if ($report->user_id !== $user->id) {
            return false;
        }

        return $report->status === Report::STATUS_DRAFT;
    }

    /**
     * Only super_admin can delete submitted reports.
     * Staff can only delete their own draft reports.
     */
    public function delete(User $user, Report $report): bool
    {
        // Only super_admin can delete any report
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Staff can only delete their own draft reports
        if ($report->user_id === $user->id && $report->status === Report::STATUS_DRAFT) {
            return true;
        }

        return false;
    }

    /**
     * Only owner can submit, and only if status is draft.
     */
    public function submit(User $user, Report $report): bool
    {
        if ($report->user_id !== $user->id) {
            return false;
        }

        return $report->status === Report::STATUS_DRAFT;
    }

    /**
     * Admin, Head of Operations can review any.
     * HOD can review department reports only.
     */
    public function review(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('head_of_operations')) {
            return true;
        }

        if ($user->isHOD() && $report->department_id === $user->department_id) {
            return true;
        }

        return false;
    }

    /**
     * Download permission is same as view.
     */
    public function download(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }
}
