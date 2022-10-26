<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemoTestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id'     => 'required|integer',
            'answer' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'id.required'     => 'ID is required!',
            'answer.required' => 'Answer is required!',
        ];
    }
}
