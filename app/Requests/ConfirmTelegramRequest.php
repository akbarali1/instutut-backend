<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: ConfirmTelegramRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 25/06/2022
 * Time: 12:33
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class ConfirmTelegramRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'telegram_id'       => 'required|integer',
            'save_telegram'     => 'required|boolean',
            'data_check_string' => 'required',
        ];
    }

}
