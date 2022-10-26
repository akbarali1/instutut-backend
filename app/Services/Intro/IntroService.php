<?php

namespace App\Services\Intro;

use App\Http\Requests\IntroCheckRequest;
use App\Http\Requests\IntroUpdateRequest;
use App\Models\DemoTest;
use App\Services\Admin\AdminService;
use App\Services\Helpers\HelpersJson;
use App\ViewModels\JsonReturnViewModel;
use Illuminate\Support\Facades\Auth;

/**
 * Created by PhpStorm.
 * Filename: IntroService.php
 * Project Name: questa-backend.loc
 * User: Akbarali
 * Date: 26/03/2022
 * Time: 1:42 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */
class IntroService
{

    public function checkPage(string $page, array $intro): array
    {
        if (array_key_exists($page, $intro)) {
            return ['status' => $intro[$page]];
        } else {
            return ['status' => 'not_found'];
        }
    }

    public function update(string $page, $status, array $intro)
    {
        if (array_key_exists($page, $intro)) {
            $intro[$page] = $status;
        } else {
            return ['status' => 'not_found'];
        }
        Auth::user()->intro = $intro;
        Auth::user()->save();

        return [
            'status'  => $status,
            'message' => 'Intro updated',
        ];
    }

    public function clear()
    {
        Auth::user()->intro = AdminService::intoArray();
        Auth::user()->save();

        return AdminService::returnSuccess([
            'status'  => 'success',
            'message' => 'Intro tozalandi',
        ]);
    }


}
