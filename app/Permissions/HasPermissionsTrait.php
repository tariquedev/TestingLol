<?php
namespace App\Permissions;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Arr;

trait HasPermissionsTrait
{
    public function givePermissionTo(...$permissions)
    {
        $permissions = $this->getAllPermissions(Arr::flatten($permissions));

        if ($permissions === null) {
            return $this;
        }

        $this->permissions()->saveMany($permissions);

        return $this;
    }

    public function withdrawPermissionTo(...$permissions)
    {
        $permissions = $this->getAllPermissions(Arr::flatten($permissions));

        $this->permissions()->detach($permissions);

    }

    /**
     * Check a user has correct permission
     */
    public function hasRole(...$roles)
    {
        foreach( $roles as $role ) {
            if ( $this->roles->contains('name', $role) ) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(...$roles)
    {
        $roles = $this->getAllRoles(Arr::flatten($roles));

        if ( $roles === null ) {
            return $this;
        }

        $this->roles()->saveMany($roles);
    }

    public function removeRole(...$roles)
    {
        $roles = $this->getAllroles(Arr::flatten($roles));

        $this->roles()->detach($roles);
    }

    public function getPermissionsThroughRole()
    {
        return $this->roles->flatMap(function($role){
            return $role->permissions;
        })->unique('name')->pluck('name');
    }

    /**
     * [hasPermissionTo description]
     *
     * @param   [type]  $permission  [$permission description]
     *
     * @return  [type]               [return description]
     */
    public function hasPermissionTo($permission)
    {
        return $this->hasPermissionThroughRole($permission);
    }

    /**
     * Check permision of user role
     *
     * @param   [type]  $permission  [$permission description]
     *
     * @return  [type]               [return description]
     */
    protected function hasPermissionThroughRole($permission)
    {
        foreach($permission->roles as $role) {
            if ($this->roles->contains($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions
     *
     * @param   [type]  $permissions  [$permissions description]
     *
     * @return  [type]                [return description]
     */
    protected function getAllPermissions($permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }

    /**
     * Get all roles
     *
     * @return  [type]  [return description]
     */
    protected function getAllRoles($roles)
    {
        return Role::whereIn('name', $roles)->get();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }
}
