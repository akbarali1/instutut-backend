<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: DemoTestCreateRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 20/08/2022
 * Time: 2:28 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class DemoTestCreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name'     => 'required|string|max:255',
            'answers'  => 'required|array',
            'cat_id'   => 'required|integer',
            'cat_name' => 'required|string',
            'time'     => 'required|string',
        ];
    }

}
