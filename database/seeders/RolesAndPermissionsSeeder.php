<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.import',

            // Department management
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // Reports
            'reports.view_all',
            'reports.view_department',
            'reports.view_own',
            'reports.create',
            'reports.edit',
            'reports.delete',
            'reports.download',
            'reports.review',

            // Comments
            'comments.create',
            'comments.edit_own',
            'comments.delete',

            // Announcements
            'announcements.view',
            'announcements.create',
            'announcements.edit',
            'announcements.delete',

            // Proposals
            'proposals.view_all',
            'proposals.view_own',
            'proposals.create',
            'proposals.edit_own',
            'proposals.delete_own',
            'proposals.review',

            // Settings/CMS
            'settings.view',
            'settings.manage',
            'pages.view',
            'pages.manage',

            // Activity logs
            'activity_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - All permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'users.view',
            'users.create',
            'users.edit',
            'users.import',
            'departments.view',
            'departments.create',
            'departments.edit',
            'reports.view_all',
            'reports.download',
            'reports.review',
            'comments.create',
            'comments.edit_own',
            'announcements.view',
            'announcements.create',
            'announcements.edit',
            'proposals.view_all',
            'proposals.review',
            'settings.view',
            'pages.view',
            'activity_logs.view',
        ]);

        // Head of Operations
        $headOfOperations = Role::create(['name' => 'head_of_operations']);
        $headOfOperations->givePermissionTo([
            'users.view',
            'departments.view',
            'reports.view_all',
            'reports.download',
            'reports.review',
            'comments.create',
            'comments.edit_own',
            'announcements.view',
            'proposals.view_all',
            'proposals.review',
        ]);

        // HOD (Head of Department)
        $hod = Role::create(['name' => 'hod']);
        $hod->givePermissionTo([
            'users.view',
            'departments.view',
            'reports.view_department',
            'reports.view_own',
            'reports.create',
            'reports.edit',
            'reports.download',
            'comments.create',
            'comments.edit_own',
            'announcements.view',
            'proposals.view_own',
            'proposals.create',
            'proposals.edit_own',
            'proposals.delete_own',
        ]);

        // Staff
        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'reports.view_own',
            'reports.create',
            'reports.edit',
            'reports.download',
            'comments.create',
            'comments.edit_own',
            'announcements.view',
            'proposals.view_own',
            'proposals.create',
            'proposals.edit_own',
            'proposals.delete_own',
        ]);
    }
}
