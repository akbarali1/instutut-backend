<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: SaveNotificationRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 04/08/2022
 * Time: 7:28 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class SaveNotificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'endpoint'        => 'required|string',
            'publicKey'       => 'required|string',
            'authToken'       => 'required|string',
            'contentEncoding' => 'required|string',
        ];
    }

}
