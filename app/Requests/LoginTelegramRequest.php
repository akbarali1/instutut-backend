<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: LoginTelegramRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 07/09/2022
 * Time: 8:44 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class LoginTelegramRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'auth_date'  => 'required|integer',
            'id'         => 'required|integer',
            'hash'       => 'required|string',
            'photo_url'  => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name'  => 'nullable|string',
            'username'   => 'nullable|string',
        ];
    }

}
