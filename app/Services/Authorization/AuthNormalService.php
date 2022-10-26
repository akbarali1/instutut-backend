<?php

namespace App\Services\Authorization;

use App\Models\AuthTokenModel;
use App\Services\Admin\AdminService;
use App\Services\Telegram\TelegramService;
use App\ViewModels\JsonReturnViewModel;
use Elliptic\EC;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use kornrunner\Keccak;

/**
 * Created by PhpStorm.
 * Filename: AuthNormalService.php
 * Project Name: questa-backend.loc
 * Author: Akbarali
 * Date: 13/04/2022
 * Time: 6:23 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class AuthNormalService
{

    public static function respondWithToken($user, $device_name = false): JsonResponse
    {
        $telegram_id = $user->telegram_id ?? false;
        $userAgent   = request()->userAgent();
        $userIp      = request()->ip();

        if (isset($telegram_id) && (new TelegramService)->sendingCheck($telegram_id)) {
            $message = "Sizning akkauntingizga kirishdi.\n";
            $message .= '<u>Soat:</u> <b>'.date('d.m.Y H:i:s')."</b>\n";
            if ($device_name) {
                $message .= '<u>Avtorizatsiya turi:</u> <b>'.$device_name."</b>\n";
            }
            $message .= '<u>IP Addres:</u> <b>'.$userIp."</b>\n";
            $message .= '<u>User Agent:</u> <b>'.$userAgent."</b>\n";
            $res     = (new TelegramService())->sendMessage($telegram_id, $message, reply_markup: [
                'inline_keyboard' => [
                    [
                        [
                            'text'          => 'Taqiqlash',
                            'callback_data' => json_encode(
                                [
                                    'loginBlock' => true,
                                    'authId'     => 11,
                                ]
                            ),
                        ],
                    ],
                ],
            ]);
            (new TelegramService())->pinChatMessage($res, $telegram_id);
        }
        $token = $user->createToken($device_name)->plainTextToken;

        return JsonReturnViewModel::toJsonBeautify([
            'status'       => 'success',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => 60,
        ])->header('Authorization', $token);
    }
}
