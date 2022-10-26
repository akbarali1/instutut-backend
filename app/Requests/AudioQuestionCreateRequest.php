<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: AudioQuestionCreateRequest.php
 * Project Name: questa-backend.loc
 * Author: Akbarali
 * Date: 27/04/2022
 * Time: 1:44 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class AudioQuestionCreateRequest extends FormRequest
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
            'files'     => 'required|mimes:application/octet-stream,audio/mpeg,mpg,ogg,mp3,wav,aiff,aif|max:2048',
        ];
    }

}
