<?php

namespace App\Services\UserStatus;


use App\Models\UserStatus;
use App\Services\Admin\AdminService;

/**
 * Created by PhpStorm.
 * Filename: UserStatusService.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 21/09/2022
 * Time: 11:26 AM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class UserStatusService
{
    public function getStatus(): array
    {
        return AdminService::returnSuccess(UserStatus::query()->get());
    }

    public function getStatusAll(): array
    {
        $all = UserStatus::query()->pluck('name', 'id');

        return AdminService::returnSuccess($all);
    }

}
