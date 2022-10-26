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
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 02/08/2022
 * Time: 6:59 PM
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
        $user_id   = $user->id;
        $stars_all = QuestionService::getUserStar($user_id, $user->status_id, true);
        $stars     = $stars_all['stars'];
        $stars_pro = $stars_all['pro'];

        return [
            'id'              => $user->id,
            'unq_id'          => $user->id_unquine,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'name'            => $user->name,
            'webmoney'        => $user->webmoney,
            'qiwi'            => $user->qiwi,
            'intro'           => $user->intro,
            'date_of_birth'   => ($user->dateo_of_birth !== null) ? $user->dateo_of_birth : ['oy' => '', 'yil' => '', 'sana' => ''],
            'last_name'       => ($user->last_name != null) ? $user->last_name : '',
            'ota'             => ($user->otasi != null) ? $user->otasi : '',
            'rights'          => $user->rights,
            'money'           => number_format($user->money, '2', '.', ''),
            'rating'          => number_format($user->rating, '2', '.', ''),
            'status_id'       => $user->status_id,
            'status_name'     => $user->status_name,
            'purchased_error' => $user->purchased_error,
            'stars_pro'       => $stars_pro,
            'status_image'    => url('/').'/assets/images/'.$user->status_id.'.png',
            'stars'           => ($stars > 0) ? $stars : 0,
            'eth_address'     => !is_null($user->eth_address),
            'telegram'        => !is_null($user->telegram_id),
        ];
    }
}
