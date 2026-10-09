<?php

namespace Database\Seeders;

use App\Domains\HR\Models\Department;
use App\Domains\HR\Models\Designation;
use App\Domains\HR\Models\Employee;
use App\Domains\HR\Models\WorkShift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Daftar permissions sesuai rbac-matrix.md
        $permissions = [
            'employee.view-any',
            'employee.create',
            'employee.update',
            'employee.view-salary',
            'attendance.view-all',
            'attendance.record-self',
            'leave.approve',
            'leave.request-self',
            'payroll.period-manage',
            'payroll.calculate',
            'payroll.approve',
            'payroll.view-all-slips',
            'payroll.view-my-slip',
            'finance.voucher-release',
            'finance.journal-view',
            'finance.coa-manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Definisi Roles
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        $executive = Role::findOrCreate('executive', 'web');
        $executive->givePermissionTo([
            'employee.view-any',
            'employee.view-salary',
            'attendance.view-all',
            'payroll.approve',
            'payroll.view-all-slips',
            'payroll.view-my-slip',
            'finance.voucher-release',
            'finance.journal-view',
        ]);

        $hrManager = Role::findOrCreate('hr-manager', 'web');
        $hrManager->givePermissionTo([
            'employee.view-any',
            'employee.create',
            'employee.update',
            'employee.view-salary',
            'attendance.view-all',
            'leave.approve',
            'payroll.period-manage',
            'payroll.calculate',
            'payroll.view-all-slips',
            'payroll.view-my-slip',
        ]);

        $financeOfficer = Role::findOrCreate('finance-officer', 'web');
        $financeOfficer->givePermissionTo([
            'employee.view-any',
            'employee.view-salary',
            'attendance.record-self',
            'payroll.view-all-slips',
            'payroll.view-my-slip',
            'finance.voucher-release',
            'finance.journal-view',
            'finance.coa-manage',
        ]);

        $employee = Role::findOrCreate('employee', 'web');
        $employee->givePermissionTo([
            'attendance.record-self',
            'leave.request-self',
            'payroll.view-my-slip',
        ]);

        // Default Users
        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'admin@intelcreative.co.id',
                'role' => 'super-admin',
            ],
            [
                'name' => 'Executive Director',
                'email' => 'executive@intelcreative.co.id',
                'role' => 'executive',
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr.manager@intelcreative.co.id',
                'role' => 'hr-manager',
            ],
            [
                'name' => 'Finance Officer',
                'email' => 'finance@intelcreative.co.id',
                'role' => 'finance-officer',
            ],
            [
                'name' => 'Staff Employee',
                'email' => 'staff@intelcreative.co.id',
                'role' => 'employee',
            ],
            [
                'name' => 'Rian Ardiansyah',
                'email' => 'rian.ardiansyah@intelcreative.co.id',
                'role' => 'employee',
            ],
            [
                'name' => 'Dewi Safitri',
                'email' => 'dewi.safitri@intelcreative.co.id',
                'role' => 'employee',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('SecretPassword123!'),
                ]
            );
            $user->syncRoles([$userData['role']]);
        }

        // Master Data HR Dasar & Profil Karyawan Staff
        $dept = Department::firstOrCreate(
            ['code' => 'CRT'],
            ['name' => 'Creative & Media']
        );

        $designation = Designation::firstOrCreate(
            ['department_id' => $dept->id, 'title' => 'Graphic & Motion Designer']
        );

        $shift = WorkShift::firstOrCreate(
            ['name' => 'Shift Reguler'],
            [
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'late_tolerance_minutes' => 15,
            ]
        );

        Employee::firstOrCreate(
            ['email' => 'staff@intelcreative.co.id'],
            [
                'nik' => 'ITC-STF-001',
                'full_name' => 'Staff Employee',
                'phone' => '081234567890',
                'department_id' => $dept->id,
                'designation_id' => $designation->id,
                'work_shift_id' => $shift->id,
                'employment_status' => 'PKWTT',
                'join_date' => '2026-01-01',
                'bank_name' => 'BCA',
                'bank_account_number' => '8830192831',
                'bank_account_holder' => 'Staff Employee',
                'basic_salary' => 8500000.00,
            ]
        );
    }
}
