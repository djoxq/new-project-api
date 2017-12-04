<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\auth\LoginRequest;
use App\Http\Requests\API\auth\RegisterRequest;
use App\Http\Requests\Request;

use App\Models\User;

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 8/30/2017
 * Time: 11:56
 */
class UsersController extends APIController
{
    public function register(RegisterRequest $request)
    {
        $newUser['first_name'] = $request->input("first_name");
        $newUser['last_name'] = $request->input("last_name");
        $newUser['email'] = $request->input("email");
        $newUser['password'] = bcrypt($request->input("password"));

        $user = User::create($newUser);

//        $scope = Scope::where('name', 'user')->first();

//        $user->scopes()->attach($scope->id);

        return $this->respondCreated($user);
    }

    public function login(LoginRequest $request) {
        return $this->respond([
            'token' => User::login($request)
        ]);
    }

    public function logout(Request $request)
    {
        User::logout($request);
        return $this->respondAccepted();
    }

    public function refreshToken(Request $request)
    {
        return $this->respond([
            'token' => User::refreshToken($request)
        ]);
    }
}