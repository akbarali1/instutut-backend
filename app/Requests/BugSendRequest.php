<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: BugSendRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 06/09/2022
 * Time: 4:38 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class BugSendRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'question_id' => 'required|integer|exists:question,id',
            'message'     => 'nullable|string',
        ];
    }
}
