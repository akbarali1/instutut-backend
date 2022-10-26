<?php
/**
 * Created by PhpStorm.
 * Filename: TestRequest.php
 * Project Name: vuejwtlaravel.loc
 * User: Akbarali
 * Date: 27/08/2021
 * Time: 12:12 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateChampionshipRequest extends FormRequest
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
            'id'           => 'nullable|integer|exists:championship,id',
            'name'         => 'required|string|max:255',
            'content'      => 'required|string',
            'minimum_ball' => 'required|integer',
            'start_time'   => 'required|string',
            'end_time'     => 'required|string',
            'count'        => 'required|integer',
            'prices'       => 'required|array',
        ];
    }
}
