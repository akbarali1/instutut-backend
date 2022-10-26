<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Created by PhpStorm.
 * Filename: ${FILE_NAME}
 * Project Name: questa-backend.loc
 * User: Akbarali
 * Date: 26/03/2022
 * Time: 7:11 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */
class NewsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id'          => 'required|integer|exists:news,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'content'     => 'required',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

}
