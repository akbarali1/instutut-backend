<?php

declare(strict_types=1);

namespace App\Services\Helpers;

use Carbon\Carbon;


class HelpersJson
{

    public static function returnError(string $message, array $array = []): array
    {
        return array_merge([
            'error'   => true,
            'message' => $message,
        ], $array);
    }

    public static function returnSuccess(string $message, $array = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $array);
    }

    public static function ExchangeError(string $message): array
    {
        return [
            "error" => [
                'answer' => $message,
            ],
        ];
    }

    public static function ExchangeSuccess(string $message, $array = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $array);
    }

    public static function returnTrueAnswer(array $str): string
    {
        return trim((string)$str['0']);
    }

    public static function endOfDay(): string
    {
        return Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->endOfDay()->toDateTimeString();
    }

    public static function startOfDay(): string
    {
        return Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->startOfDay()->toDateTimeString();
    }

    public static function NumberFormat(int $number): string
    {
        return number_format((float)$number, 2, '.', '');
    }

    public static function getImageUrl($image, $id): bool|string
    {

        $photo_yes = public_path('assets/images/tests/'.$id.'.png');

        return (file_exists($photo_yes)) ? url('/').'/public/assets/images/tests/'.$id.'.png' : false;
    }

    public static function getPhoto($photo)
    {
        return $photo ? route('welcome').$photo : $photo;
    }

}
