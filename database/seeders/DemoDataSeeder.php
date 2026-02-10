<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create departments
        $itDept = Department::create([
            'name' => 'Information Technology',
            'description' => 'IT and software development team',
            'is_active' => true,
        ]);

        $hrDept = Department::create([
            'name' => 'Human Resources',
            'description' => 'HR and people management',
            'is_active' => true,
        ]);

        $financeDept = Department::create([
            'name' => 'Finance',
            'description' => 'Financial operations and accounting',
            'is_active' => true,
        ]);

        $operationsDept = Department::create([
            'name' => 'Operations',
            'description' => 'Day to day operations management',
            'is_active' => true,
        ]);

        // Create Super Admin
        $superAdmin = User::create([
            'kingschat_id' => 'superadmin001',
            'title' => 'Pastor',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@example.com',
            'phone' => '08012345678',
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // Create Admin
        $admin = User::create([
            'kingschat_id' => 'admin001',
            'title' => 'Pastor',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '08012345679',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Create Head of Operations
        $headOps = User::create([
            'kingschat_id' => 'headops001',
            'title' => 'Deacon',
            'first_name' => 'Head',
            'last_name' => 'Operations',
            'email' => 'headops@example.com',
            'phone' => '08012345680',
            'department_id' => $operationsDept->id,
            'is_active' => true,
        ]);
        $headOps->assignRole('head_of_operations');

        // Create HODs
        $itHod = User::create([
            'kingschat_id' => 'ithod001',
            'title' => 'Brother',
            'first_name' => 'IT',
            'last_name' => 'Manager',
            'email' => 'ithod@example.com',
            'phone' => '08012345681',
            'department_id' => $itDept->id,
            'is_active' => true,
        ]);
        $itHod->assignRole('hod');
        $itDept->update(['head_id' => $itHod->id]);

        $hrHod = User::create([
            'kingschat_id' => 'hrhod001',
            'title' => 'Sister',
            'first_name' => 'HR',
            'last_name' => 'Manager',
            'email' => 'hrhod@example.com',
            'phone' => '08012345682',
            'department_id' => $hrDept->id,
            'is_active' => true,
        ]);
        $hrHod->assignRole('hod');
        $hrDept->update(['head_id' => $hrHod->id]);

        // Create Staff members
        $staffMembers = [
            [
                'kingschat_id' => 'staff001',
                'title' => 'Brother',
                'first_name' => 'John',
                'last_name' => 'Developer',
                'email' => 'john@example.com',
                'phone' => '08012345683',
                'department_id' => $itDept->id,
            ],
            [
                'kingschat_id' => 'staff002',
                'title' => 'Sister',
                'first_name' => 'Jane',
                'last_name' => 'Designer',
                'email' => 'jane@example.com',
                'phone' => '08012345684',
                'department_id' => $itDept->id,
            ],
            [
                'kingschat_id' => 'staff003',
                'title' => 'Brother',
                'first_name' => 'Mike',
                'last_name' => 'HRStaff',
                'email' => 'mike@example.com',
                'phone' => '08012345685',
                'department_id' => $hrDept->id,
            ],
            [
                'kingschat_id' => 'staff004',
                'title' => 'Sister',
                'first_name' => 'Sarah',
                'last_name' => 'Finance',
                'email' => 'sarah@example.com',
                'phone' => '08012345686',
                'department_id' => $financeDept->id,
            ],
        ];

        foreach ($staffMembers as $staffData) {
            $staff = User::create(array_merge($staffData, ['is_active' => true]));
            $staff->assignRole('staff');
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo Accounts (use KingsChat ID to login):');
        $this->command->info('-------------------------------------------');
        $this->command->info('Super Admin: superadmin001 (phone: 08012345678)');
        $this->command->info('Admin: admin001 (phone: 08012345679)');
        $this->command->info('Head of Ops: headops001 (phone: 08012345680)');
        $this->command->info('IT HOD: ithod001 (phone: 08012345681)');
        $this->command->info('Staff: staff001 (phone: 08012345683)');
    }
}
