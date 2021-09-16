<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UserService
{
    private User $user;

    public function user(User $user): UserService
    {
        $this->user = $user;

        return $this;
    } //end method user

    public function getUser(): User
    {
        return $this->user;
    }

    public function clearUser(): UserService
    {
        unset($this->user);

        return $this;
    } //end method clearUser

    /**
     * @param array|object $data
     * @param array|object $excludeColumns
     * @param Illuminate\Http\Request $request
     * For an update, the user must be passed into the user() 
     * 
     * Usage example
     * 
     * $updatedOrNewUser = $userService
     *          ->clearUser()
     *          ->user($user)
     *          ->updateOrCreateUser(["about" => "lorem ipsum"])
     *          ->save()
     *          ->getUser();
     */
    public function updateOrCreateUser($data, $excludeColumns = []): UserService
    {
        // exclude some columns because they are not nmecessarily strings
        // or the data might be handled differently
        $excludeDataColumns = ["password"];

        $excludeDataColumns = array_merge($excludeDataColumns, $excludeColumns);

        // if user is not defined create a new one
        $user = $this->user ?? new User();
        $columns = Schema::getColumnListing($user->getTable());

        // loop through all data column and update the user column with
        // the value from the data if it exists in the $data object
        foreach ($columns as $column) {
            //if column is to be excluded
            if (in_array($column, $excludeDataColumns)) continue;

            $user[$column] = $data[$column] ?? $user[$column];

            //unset null $user properties to allow the database assign a default
            //value for default column values
            if ($user[$column] == null)
                unset($user[$column]);
        } //end columns loop

        //=================================
        // Handle excluded columns
        //================================

        //Password
        if ($data["password"]) {
            $user->password = Hash::make($data["password"]);
        }

        $this->user($user);
        return $this;
    } //end method updateOrCreateUser

    public function save(): UserService
    {
        $this->user->save();
        return $this;
    } //end method save

}//end class UserService