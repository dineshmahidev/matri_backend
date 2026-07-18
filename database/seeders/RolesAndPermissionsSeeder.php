<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create permissions
        $permissions = [
            'view dashboard',
            'view users',
            'edit users',
            'delete users',
            'view packages',
            'edit packages',
            'delete packages',
            'view payments',
            'edit payments',
            'delete payments',
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions

        // this can be done as separate statements
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        $roleManager = Role::firstOrCreate(['name' => 'manager']);
        $roleManager->givePermissionTo([
            'view dashboard',
            'view users',
            'edit users',
            'view packages',
            'edit packages',
            'view payments',
            'view reports'
        ]);

        $roleStaff = Role::firstOrCreate(['name' => 'staff']);
        $roleStaff->givePermissionTo([
            'view dashboard',
            'view users',
        ]);
    }
}
