<?php

namespace App\Services\Admin;

use App\Services\Telegram\TelegramService;
use Carbon\Carbon;

class AdminService
{

    public static function getDefaultCarbonDateFormat(): string
    {
        return 'd.m.Y';
    }

    public static function replaceCarbonDateFormat($time): string
    {
        return Carbon::parse($time)->format('d.m.Y');
    }

    public static function parseCarbonDateFormat($time): string
    {
        return Carbon::parse($time)->format('d.m.Y h.i');
    }

    public static function getWeekday($day): string
    {
        return __(date('l', strtotime($day)));
    }

    public static function getDate(): string
    {
        return date('Y-m-d');
    }

    public static function getDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function getPaginationLimit(): int
    {
        return 40;
    }

    public static function getCarbonSubdaysDefault(): string
    {
        return Carbon::now()->subDays(30)->format('Y-m-d');
    }

    public static function getCarbonOneDay(): string
    {
        return Carbon::now()->subDays(1)->format('Y-m-d');
    }

    public static function getNumberFormat($money): int|string
    {
        return !empty($money) && is_numeric($money) ? number_format($money, 2) : 0;

    }

    public static function PrintNumberFormat($money): int|string
    {
        if (!empty($money) && is_numeric($money)) {
            return number_format($money, '2', '.', '');
        }

        return 0;
    }

    public static function getPrintNumberFormat($money): int|string
    {
        if (!empty($money) && is_numeric($money)) {
            return number_format($money);
        }

        return 0;
    }

    public static function getCarbonThreeDay(): string
    {
        return Carbon::now()->subDays(3)->format('Y-m-d');
    }

    public static function getCarbonOneWeek(): string
    {
        return Carbon::now()->subDays(7)->format('Y-m-d');
    }

    public static function getCarbonThreeMonth(): string
    {
        return Carbon::now()->subDays(90)->format('Y-m-d');
    }

    public static function getCarbonSixMonth(): string
    {
        return Carbon::now()->subDays(180)->format('Y-m-d');
    }

    public static function getCarbonOneYear(): string
    {
        return Carbon::now()->subDays(365)->format('Y-m-d');
    }

    public static function getCarbonTwoYear(): string
    {
        return Carbon::now()->subDays(730)->format('Y-m-d');
    }

    public static function QuestionNotFound($message = 'Question Not Found'): array
    {
        return [
            'error'   => true,
            "message" => $message,
        ];
    }

    public static function returnSuccess($questions = []): array
    {
        return [
            'success' => true,
            "data"    => $questions,
        ];
    }

    public static function emptyResponse(): array
    {
        return [
            'ok'     => true,
            'result' => true,
        ];
    }

    public static function returnError($name = ['message' => 'Bunday ma\'lumot topilmadi']): array
    {
        return [
            'error' => true,
            "data"  => $name,
        ];
    }

    public static function intoArray(): array
    {
        return [
            "home"     => false,
            "test"     => false,
            "exchange" => false,
            "settings" => false,
        ];
    }

    public static function generateRandomString($length = 6): string
    {
        $original_string = array_merge(range(0, 29), range('a', 'z'), range('A', 'Z'));
        $original_string = implode("", $original_string);

        return substr(str_shuffle($original_string), 0, $length);
    }

    public static function changeTimeFormat($time, $format = 'Y-m-d\TH:i:s'): string
    {
        return Carbon::parse($time)->format($format);
    }

    public static function getPicture($photo, int $id)
    {
        $extension = pathinfo($photo, PATHINFO_EXTENSION);
        $path      = url('/').'/assets/images/tests/'.$id;

        if ($photo == '' && $extension == '') {
            return false;
        }

        return ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png' || $extension === 'jfif') ? $path.'.png' : $path.'.'.$extension;

    }

    public static function questionAnswerToArrayShuffle($answer, $string = false): array
    {
        if ($string && !is_array($answer)) {
            $answer = json_decode($answer, true);
        }
        $array = [trim($answer[0]), trim($answer[1]), trim($answer[2]), trim($answer[3])];
        shuffle($array);

        return $array;
    }

    public static function getQuestionTypeNumber($id, bool $type = false, $file = null): string
    {
        $collection = [
            'image' => '1',
            'text'  => '2',
            'audio' => '3',
            'video' => '4',
        ];

        if ($type === true) {
            return array_search($id, $collection) ? array_search($id, $collection) : ((is_null($file)) ? 'wwwwwww' : 'image');
        }

        return $collection[$id] ?? 'aaaaaaaaaaa';
    }

    public static function extFindId($ext): int
    {
        $arr = [
            1 => [
                'png',
                'jpg',
                'jpeg',
                'gif',
            ],
            2 => [
                'text',
            ],
            3 => [
                'ogg',
                'mp3',
                'aac',
            ],
            4 => [
                'mp4',
                '3gp',
            ],
        ];
        foreach ($arr as $key => $item) {
            if (in_array($ext, $item, true)) {
                return $key;
            }
        }

        return 2;
    }

    public static function extFindType($ext): string
    {
        $arr = [
            'image' => [
                'png',
                'jpg',
                'jpeg',
                'gif',
            ],

            'audio' => [
                'ogg',
                'mp3',
                'aac',
            ],
            'video' => [
                'mp4',
                '3gp',
            ],
        ];
        foreach ($arr as $key => $item) {
            if (in_array($ext, $item, true)) {
                return $key;
            }
        }

        return 'text';
    }

    public static function getFile($file, int $id, string $type): string
    {
        if (is_null($file)) {
            return '';
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($extension === '') {
            return '';
        }
        $file_image = url('/public').asset('images/tests/'.$id.'.'.$extension);
        $file_exist = public_path('/assets/images/tests/').$id.'.'.$extension;

        if (file_exists($file_exist)) {
            return $file_image;
        }

        switch (self::getQuestionTypeNumber($type, true, $file)) {
            case 'image':
                $path = url('/').'/assets/images/tests/'.$id;
                break;
            case 'audio':
                $path = url('/').'/assets/audios/tests/'.$id;
                break;
            default:
                return '';
        }
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png' || $extension === 'jfif') {
            return $path.'.png';
        }

        return $path.'.'.$extension;
    }

    public static function saveFile($file, int $id, string $file_path = 'user_photo', $type = 'url'): string|bool
    {
        if (is_null($file) && $file_path !== 'user_photo') {
            return '';
        }
        $path = '/assets/images/'.$file_path;

        $file_path = $path.'/'.$id.'.'.pathinfo($file, PATHINFO_EXTENSION);
        if ($type === 'url') {
            if (!file_exists(public_path($path)) && !mkdir($concurrentDirectory = public_path($path), 0777, true) && !is_dir($concurrentDirectory)) {
                TelegramService::AdminsNotification('Rasmni saqlashda xatolik Adminservis saveFile');
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            copy($file, public_path($file_path));

            return $file_path;
        }

        return false;
    }

    public static function getRatingLimit(): int
    {
        return 10;
    }

}
