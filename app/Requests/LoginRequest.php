<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: LoginRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 25/06/2022
 * Time: 12:33
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email'             => 'required|email',
            'password'          => 'required|min:6',
            'data_check_string' => 'required',
            'telegram_id'       => 'required|integer',
        ];
    }

}
