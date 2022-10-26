<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: SurfingRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 11/09/2022
 * Time: 12:40 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class SurfingRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'id'          => 'nullable|exists:surfing,id',
            'name'        => 'required|string',
            'description' => 'required|string|max:255',
            'site_url'    => 'required|string|max:255',
            'time'        => 'required|integer',
            'money'       => 'required|numeric',
            'rating'      => 'required|numeric',
            'error'       => 'required|numeric',
        ];
    }

}
