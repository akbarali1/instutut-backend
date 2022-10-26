<?php
/**
 * Created by PhpStorm.
 * Filename: TestCheckRequest.php
 * Project Name: vuejwtlaravel.loc
 * User: Akbarali
 * Date: 11/08/2021
 * Time: 12:12 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestCheckRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'DemoTest'    => 'required|array',
            'DemoTest.*'  => 'integer',
            'DemoJavob'   => 'array',
            'DemoJavob.*' => 'integer',
            'Answer'      => 'required|string',
            'QuestionId'  => 'required|exists:demo_test,id',
        ];
    }

}
