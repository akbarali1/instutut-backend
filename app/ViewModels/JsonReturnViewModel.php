<?php
/**
 * Created by PhpStorm.
 * Filename: JsonReturnViewModel.php
 * User: Akbarali
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */

namespace App\ViewModels;

use Illuminate\Http\JsonResponse;

class JsonReturnViewModel
{
    public static function toJsonBeautify(array $data = [], $status = 200): JsonResponse
    {
        return response()->json(
            $data,
            $status,
            [
                'Content-Type' => 'application/json',
                'Charset'      => 'UTF-8',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        //Siqilgan holatda jo`natiladigan qilindi
        // JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
