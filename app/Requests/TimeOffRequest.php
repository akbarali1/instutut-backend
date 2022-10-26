<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: TimeOffRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 01/09/2022
 * Time: 12:31 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class TimeOffRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'number' => [
                'required',
                Rule::in([1, 2, 5, 12, 24]),
            ],
        ];
    }

}
