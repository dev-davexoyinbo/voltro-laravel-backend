<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\RoleAndPriviledgeServiceException;
use App\Models\Permission;

class RoleAndPriviledgeService
{
    private User $user;

    public function user(User $user): RoleAndPriviledgeService
    {

        $this->user = $user;

        return $this;
    } //end method usere

    public function clearUser(): RoleAndPriviledgeService
    {
        unset($this->user);

        return $this;
    } //end method clearUser

    //Returns [permissions, roles]
    public function getPermissionAndRoleList()
    {
        $user = $this->user;

        if (!$user) {
            throw new RoleAndPriviledgeServiceException("The user is not defined");
        }
        $permissions = $user->directPermissions;

        $user->loadMissing("roles", "roles.permissions");

        $roles = $user->roles;

        // $roles = $user->roles()->with("permissions")->get();

        $roles->each(function ($role) use (&$permissions) {
            $permissions = $permissions->merge($role->permissions);
        });

        $rv = [
            array_unique($permissions->pluck("name")->all()),
            array_unique($roles->pluck("name")->all())
        ];

        return $rv;
    } //end method getPermissions

    public function hasPermission($permission): bool
    {
        return in_array($permission, $this->getPermissionAndRoleList()[0]);
    } //end method hasPermission

    public function hasRole($role): bool
    {
        return $this->user->roles()->where("roles.name", $role)->exists();
    } //end method 
}//end class RoleAndPriviledgeService