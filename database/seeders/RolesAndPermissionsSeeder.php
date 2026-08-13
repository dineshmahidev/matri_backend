<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Aligns with public_html admin role flow: dot-notation permissions
     * grouped by module (users.*, packages.*, etc.) for admin/manager/staff.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Users & members
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'users.bulk-add.view', 'users.bulk-add.create',
            'users.credits.edit',

            // Packages
            'packages.view', 'packages.create', 'packages.edit', 'packages.delete',

            // CMS
            'cms.view', 'cms.edit',

            // Leads
            'leads.view', 'leads.create', 'leads.edit', 'leads.delete',

            // Reference data
            'reference_data.view', 'reference_data.edit',

            // Payments
            'payments.view', 'payments.approve', 'payments.reject',

            // Reports
            'reports.view',

            // Staff
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            'staff_notes.view', 'staff_notes.edit',

            // Support
            'support_tickets.view', 'support_tickets.edit',

            // Roles
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',

            // Settings / maintenance
            'settings.view', 'settings.edit',
            'maintenance.view', 'maintenance.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Remove legacy space-separated duplicates (e.g. "view packages")
        // that collide with the new dot-notation names in the UI.
        Permission::where('guard_name', 'web')
            ->whereNotIn('name', $permissions)
            ->each(function (Permission $permission) {
                $permission->roles()->detach();
                $permission->delete();
            });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $all = Permission::where('guard_name', 'web')->pluck('name')->toArray();

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($all);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($all);

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'dashboard.view',
            'users.view', 'users.create', 'users.edit',
            'users.bulk-add.view', 'users.bulk-add.create',
            'packages.view', 'packages.edit',
            'cms.view', 'cms.edit',
            'leads.view', 'leads.create', 'leads.edit',
            'reference_data.view',
            'payments.view',
            'reports.view',
            'support_tickets.view', 'support_tickets.edit',
            'settings.view',
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'dashboard.view',
            'users.view', 'users.create',
            'leads.view', 'leads.create', 'leads.edit',
            'staff_notes.view', 'staff_notes.edit',
        ]);
    }
}
