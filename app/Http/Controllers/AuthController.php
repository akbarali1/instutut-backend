<?php

namespace App\Http\Controllers;

use App\Requests\RegisterRequest;
use App\Services\Admin\AdminService;
use App\Services\Authorization\AuthNormalService;
use App\Services\GetUserService;
use App\ViewModels\JsonReturnViewModel;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
        $data = $request->validated();
        $user = User::query()->create([
            'name'       => $data['name'],
            'year'       => $data['year'],
            'email'      => $data['email'],
            'id_unquine' => AdminService::generateRandomString(),
            'intro'      => AdminService::intoArray(),
            'password'   => bcrypt($data['password']),
            'ref_id'     => $data['ref_id'] ?? null,
        ]);

        return JsonReturnViewModel::toJsonBeautify(['status' => 'success', 'message' => 'User created successfully', 'data' => $user]);
    }

    /**
     * Login user and return a token
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return JsonReturnViewModel::toJsonBeautify(['error' => 'Avtorizatsiya amalga oshirilmadi']);
        }

        return AuthNormalService::respondWithToken($token, 'Oddiy login parol');
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
    //
    //    public function phpVersion()
    //    {
    //        $php_version = 'PHP version: '.phpversion();
    //        dd($php_version);
    //    }

    //    public function tetsuchun()
    //    {
    //        $not_table_name = 'championship_sessions';
    //        $user_id        = 1;
    //        $status_id      = 1;
    //
    //
    //        $sql = 'select `id`, `question`, `answer`,`money`, `time`, `rating`, `file`, `type` from `question` where `status_id` = '.$status_id.' and `deleted_at` is null and `id` not in ( select `question_id` from `'.$not_table_name.'` where `user_id` = '.$user_id.' and `status` = "1" and `deleted_at` is null ) order by RAND() limit 1';
    //
    //        $question = DB::select($sql);
    //        dd($sql, $question);
    //        if (!$question) {
    //            return AdminService::returnError('No questions');
    //        }
    //        $question       = $question[0];
    //        $question->file = AdminService::getFile($question->file, $question->id, $question->type);
    //
    //        dd($question);
    //
    //
    //        //$req  = $db->query('');
    //        //
    //        //        $question = Question::query()->where('status', 1)
    //        //            //                    ->where('status_id', $status_id)
    //        //            //->where('type', '=', '1')
    //        //            //            ->where('type', '=', '3')
    //        //            ->whereNotIn('id', function ($query) use ($user_id, $not_table_name) {
    //        //                $query->select('question_id')
    //        //                    ->where('user_id', $user_id)
    //        //                    ->where('status', 1)
    //        //                    ->whereNull('deleted_at')
    //        //                    ->from($not_table_name);
    //        //            })
    //        //            ->inRandomOrder()
    //        //            ->select(['id', 'question', 'answer', 'time', 'money', 'rating', 'file', 'type'])->first();
    //
    //        dd($question);
    //    }

}
