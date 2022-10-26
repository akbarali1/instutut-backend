<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: UserCryptoChangeRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 25/10/2022
 * Time: 6:14 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class UserCryptoChangeRequest extends FormRequest
{

    public function rules()
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'crypto'  => 'required|integer',
        ];
    }
}
