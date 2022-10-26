<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: QuestionCreateRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 23/09/2022
 * Time: 10:27 AM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class QuestionCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'answers'   => 'required|json',
            'money'     => 'required|integer|min:1',
            'rating'    => 'required|integer|min:1',
            'time'      => 'required|integer|min:1',
            'status_id' => 'required|exists:user_status,id',
        ];
    }

}
