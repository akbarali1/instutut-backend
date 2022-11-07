<?php

namespace App\Services;

use App\Models\ChampionshipSessions;
use App\Models\User;
use App\Services\Championship\ChampionshipService;
use App\Services\Helpers\HelpersJson;
use App\Models\PurchasedError;
use App\Models\TestSessions;
use App\Models\UserStatus;
use App\Services\Question\QuestionService;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Auth;

/**
 * Created by PhpStorm.
 * Filename: GetUserService.php
 * Project Name: instutut-backend
 * Author: Акбарали
 * Date: 07/11/2022
 * Time: 5:49 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class GetUserService
{

    protected ChampionshipService $championshipService;

    public function __construct(ChampionshipService $championshipService)
    {
        $this->championshipService = $championshipService;
    }

    public function index()
    {
        $user      = auth()->user();

        return [
            'id'              => $user->id,
            'unq_id'          => $user->id_unquine,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'name'            => $user->name,
            'intro'           => $user->intro,
            'last_name'       => $user->last_name ?? '',
            'ota'             => $user->otasi ?? '',
            'rights'          => $user->rights,
            'money'           => number_format($user->money, '2', '.', ''),
            'rating'          => number_format($user->rating, '2', '.', ''),
            'status_id'       => $user->status_id,
            'status_name'     => $user->status_name,
            'status_image'    => url('/').'/assets/images/'.$user->status_id.'.png',
            'telegram'        => !is_null($user->telegram_id),
        ];
    }
}
