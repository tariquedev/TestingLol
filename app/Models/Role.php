<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;


class Role extends Model
{
    use HasFactory;

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
        $permissions = $this->getAllPermissions(array_flatten($permissions));

        $this->permissions()->detach($permissions);

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

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_roles');
    }
}
