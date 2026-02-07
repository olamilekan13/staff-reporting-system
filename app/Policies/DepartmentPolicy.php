<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * All authenticated users can list departments (reference data).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * All authenticated users can view a department.
     */
    public function view(User $user, Department $department): bool
    {
        return true;
    }

    /**
     * Only admins can create departments.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can update departments.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can delete departments.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admins can manage department staff assignments.
     */
    public function manageStaff(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }
}
