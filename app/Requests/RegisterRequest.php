<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: RegisterRequest.php
 * Project Name: questa-backend.loc
 * Author: Akbarali
 * Date: 22/04/2022
 * Time: 6:13 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'     => 'required|min:3',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:3|confirmed',
            'ref_id'   => 'nullable|exists:users,id',
            'type'     => [
                'required',
                Rule::in(['student', 'teacher']),
            ],
        ];
    }

}
