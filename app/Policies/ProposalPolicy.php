<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    /**
     * All authenticated users can list proposals (filtered by their access level).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the proposal.
     */
    public function view(User $user, Proposal $proposal): bool
    {
        // Owner can view their own
        if ($proposal->user_id === $user->id) {
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

        return false;
    }

    /**
     * All authenticated users can create proposals (except super_admin only).
     */
    public function create(User $user): bool
    {
        // Super admin only accounts shouldn't create proposals
        if ($user->isSuperAdmin() && !$user->hasRole('admin')) {
            return false;
        }

        return true;
    }

    /**
     * Owner can update if status is pending.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        if ($proposal->user_id !== $user->id) {
            return false;
        }

        return $proposal->canBeEdited();
    }

    /**
     * Owner can delete if status is pending or rejected.
     */
    public function delete(User $user, Proposal $proposal): bool
    {
        if ($proposal->user_id !== $user->id) {
            return false;
        }

        return $proposal->canBeDeleted();
    }

    /**
     * Only admin and head_of_operations can review proposals.
     */
    public function review(User $user, Proposal $proposal): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('head_of_operations')) {
            return true;
        }

        return false;
    }

    /**
     * Download permission is same as view.
     */
    public function download(User $user, Proposal $proposal): bool
    {
        return $this->view($user, $proposal);
    }
}
