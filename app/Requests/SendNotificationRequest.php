<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Created by PhpStorm.
 * Filename: SendNotificationRequest.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 04/08/2022
 * Time: 7:28 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class SendNotificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'image'   => 'nullable|string|max:255',
            'message' => 'required|string|max:255',
            'title'   => 'nullable|string|max:50',
            'url'     => 'required|string|max:255',
        ];
    }

}
