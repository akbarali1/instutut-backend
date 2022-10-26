<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChartDateRequest extends FormRequest
{

    public function rules()
    {
        return [
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ];
    }

}
