<?php

namespace App\Http\Controllers;

use App\Requests\RegisterRequest;
use App\Services\Admin\AdminService;
use App\Services\Authorization\AuthNormalService;
use App\Services\GetUserService;
use App\ViewModels\JsonReturnViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Created by PhpStorm.
 * Filename: AuthController.php
 * Project Name: instutut-backend
 * Author: Акбарали
 * Date: 26/10/2022
 * Time: 11:10 AM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class AuthController extends Controller
{

    private GetUserService    $service;
    private AuthNormalService $authNormalService;

    public function __construct(GetUserService $service, AuthNormalService $authNormalService)
    {
        $this->service           = $service;
        $this->authNormalService = $authNormalService;
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        $data   = $request->validated();
        $rights = $data['type'] === 'student' ? User::STUDENT_STATUS : User::TEACHER_STATUS;
        $user   = User::query()->create([
            'name'      => $data['name'],
            'year'      => 0,
            'email'     => $data['email'],
            'id_unique' => AdminService::generateRandomString(),
            'intro'     => AdminService::intoArray(),
            'password'  => bcrypt($data['password']),
            'ref_id'    => $data['ref_id'] ?? null,
            'rights'    => $rights,
        ]);

        return JsonReturnViewModel::toJsonBeautify([
            'status'  => 'success',
            'message' => 'User created successfully',
            'data'    => $user,
        ]);
    }

    /**
     * Login user and return a token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required',
            'device_name' => 'required',
        ]);
        $user = User::query()->where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return JsonReturnViewModel::toJsonBeautify(['error' => 'Avtorizatsiya amalga oshirilmadi']);
        }

        return AuthNormalService::respondWithToken($user, $request->device_name);
    }

    /**
     * Logout User
     */
    public function logout()
    {
        $this->guard()->logout();

        return JsonReturnViewModel::toJsonBeautify([
            'status' => 'success',
            'msg'    => 'Logged out Successfully.',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user()
    {

        $data          = $this->service->index();
        $data['title'] = trans('all.home');
        $data['g']     = 1;

        return JsonReturnViewModel::toJsonBeautify([
            'success'  => true,
            'userData' => $data,
        ]);

    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        if ($token = ($this->guard()->refresh() === null)) {
            return JsonReturnViewModel::toJsonBeautify(['status' => 'successs'])->header('Authorization', $token);
        }

        return JsonReturnViewModel::toJsonBeautify(['error' => 'refresh_token_error'], 401);
    }

    /**
     * Return auth guard
     */
    private function guard()
    {
        return Auth::guard();
    }

    public function getUserName($user_id)
    {
        $user = User::query()->select(['name'])->findOrFail($user_id);

        return JsonReturnViewModel::toJsonBeautify([
            'success' => true,
            'name'    => $user->name,
        ]);
    }

}
