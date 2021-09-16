<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\AuthenticationServiceException;
use App\Exceptions\UserServiceException;
use App\Models\Role;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuthenticationService
{
    private User $user;
    private UserService $userService;

    public function __construct()
    {
        $this->userService = App::make(UserService::class);
    } //end constructor

    public function getUser(): User
    {
        return $this->user;
    } //end method getUser

    public function registerUser($data): AuthenticationService
    {
        // check if email exists in the database
        if (User::whereRaw(DB::raw("LOWER(email) = ?"), [strtolower($data["email"])])->exists()) {
            throw new AuthenticationServiceException("Email already in use", 400);
        }

        try {
            //createOrUpdate the user
            $user = $this->userService
                ->clearUser()
                ->updateOrCreateUser($data)
                ->save()
                ->getUser();
        } catch (UserServiceException $e) {
            throw new AuthenticationServiceException(UserServiceException::class . ": {$e->getMessage()}", $e->getCode());
        }
        $this->user = $user;
        return $this;
    } //end method registerUser

    public function login($data): string
    {
        $email = $data["email"];
        $password = $data["password"];

        $token = auth()->attempt(["email" => $email, "password" => $password]);

        if (!$token) {
            throw new AuthenticationServiceException("Email/Password combination not correct");
        }

        return $token;
    } //end method login

    public function logout()
    {
        try {
            auth()->logout();
        } catch (Throwable $e) {
            throw new AuthenticationServiceException($e->getMessage(), 500);
        }
    } //end method logout

    public function me(): User
    {
        $rolePriviledgeService = App::make(RoleAndPriviledgeService::class);

        $user = auth()->user();

        $returnVal = $rolePriviledgeService->user($user)->getPermissionAndRoleList();
        unset($user->permisisons);
        unset($user->roles);
        [$user->permissions, $user->roles]  = $returnVal;
        return $user;
    } //end method me
}//end class AuthenticationService