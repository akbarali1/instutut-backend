<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: Web3VerifyRequest.php
 * Project Name: questa-backend.loc
 * Author: Akbarali
 * Date: 16/04/2022
 * Time: 3:49 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class Web3VerifyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address'      => 'required|string',
            'signature'    => 'required|string',
            'sign_message' => 'required|string',
        ];
    }

}
