<?php
/**
 * Created by PhpStorm.
 * Filename: SerringsRequest.php
 * Project Name: vuejwtlaravel.loc
 * User: Akbarali
 * Date: 31/08/2021
 * Time: 5:36 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
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
            'familiya' => 'string',
            'ism'      => 'required|string',
            'ota'      => 'required|string',
            'phone'    => 'nullable|string',
            'qiwi'     => 'nullable|string',
            'webmoney' => 'nullable|string',
            'sana'     => 'required|string',
            'oy'       => 'required|string',
            'yil'      => 'required|string',
        ];
    }
}
