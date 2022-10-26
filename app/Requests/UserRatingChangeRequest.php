<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: UserRatingChangeRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 22/09/2022
 * Time: 11:25 AM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class UserRatingChangeRequest extends FormRequest
{

    public function rules()
    {
        return [
            'user_id'  => 'required|int|exists:users,id',
            'rating'   => 'required|int',
            'checkbox' => 'required|boolean',
        ];
    }

}
