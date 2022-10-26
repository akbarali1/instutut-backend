<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImageQuestionCreateRequest extends FormRequest
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
            'files'     => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ];
    }

}
