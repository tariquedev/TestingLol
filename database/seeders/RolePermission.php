<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'manage-app',
            'edit-user',
            'edit-product',
            'edit-others-produc',
            'edit-profile'
        ];

        foreach($permissions as $name)
        {
            $permission = new Permission;
            $permission->name = $name;

            $permission->save();
        }

        $roles = [
            'admin' => $permissions,
            'subscriber' => ['edit-product', 'edit-profile']
        ];

        foreach( $roles as $name => $permissions ) {
            $role = new Role;
            $role->name = $name;

            $role->save();

            $role->givePermissionTo($permissions);
        }
    }
}
