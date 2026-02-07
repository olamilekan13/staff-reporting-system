<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine if the user can view comments on a resource.
     * User must be able to view the parent resource.
     */
    public function viewAny(User $user, $commentable): bool
    {
        return $this->canAccessCommentable($user, $commentable);
    }

    /**
     * Determine if the user can create a comment.
     * User must be able to view the parent resource.
     */
    public function create(User $user, $commentable): bool
    {
        return $this->canAccessCommentable($user, $commentable);
    }

    /**
     * Determine if the user can update the comment.
     * Only the owner can edit their own comments.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the comment.
     * Owner can delete their own, admin can delete any.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        return $user->isAdmin();
    }

    /**
     * Check if user can access the commentable resource.
     */
    private function canAccessCommentable(User $user, $commentable): bool
    {
        if ($commentable instanceof Report) {
            return $this->canViewReport($user, $commentable);
        }

        if ($commentable instanceof Proposal) {
            return $this->canViewProposal($user, $commentable);
        }

        return false;
    }

    /**
     * Check if user can view a report.
     */
    private function canViewReport(User $user, Report $report): bool
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
     * Check if user can view a proposal.
     * Following similar logic to reports.
     */
    private function canViewProposal(User $user, Proposal $proposal): bool
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
}
