<?php

namespace App\Services\Test;

use App\Models\ChampionshipSessionsModel;
use App\Models\Question;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use App\Services\Helpers\HelpersJson;
use App\Services\Question\QuestionService;
use App\Services\Telegram\TelegramService;
use App\ViewModels\Question\QuestionTestViewModel;
use Auth;
use App\Models\TimeOff;
use App\Models\TestSessions;
use App\Models\UserStatus;
use App\Models\PurchasedError;

class TestStartService
{
    public function getTest(): array
    {
        if ((new ChampionshipService)->active() === true) {
            return HelpersJson::returnError(trans('all.chempionat_active'), [
                'title'    => trans('all.chempionat_active'),
                'type'     => 'error',
                'class'    => 'alert-error',
                'back_url' => '/dashboard',
            ]);
        }
        if (time() <= strtotime('2021-12-25 12:00:00')) {
            $response = [
                'title'    => 'Test umuman qolmagan',
                'type'     => 'error',
                'class'    => 'alert-success',
                'back_url' => '/dashboard',
            ];

            return HelpersJson::returnError('Test yechish vaqti xali boshlanmadi. Kuting tez orada boshlanadi', $response);
        }

        return QuestionService::getTest(
            auth()->user()->id,
            auth()->user()->status_id,
            auth()->user()->rating,
            auth()->user()->money,
            'test',
        );

        //        $user_id     = Auth::user()->id;
        //        $status_id   = Auth::user()->status_id;
        //        $user_rating = Auth::user()->rating;
        //        $user_money  = Auth::user()->money;

        //
        //        $question = QuestionService::getQuestion($user_id, $status_id,);
        //
        //        if (isset($question['error']) && $question['error'] === true) {
        //            $response = [
        //                'title'    => 'Test umuman qolmagan',
        //                'type'     => 'error',
        //                'class'    => 'alert-success',
        //                'back_url' => '/dashboard',
        //            ];
        //
        //            return HelpersJson::returnError(trans('all.savollar_qolmadi'), $response);
        //        }
        //        $errorsdb    = TestSessions::query()->where('user_id', $user_id)->where('status', '0')->whereBetween('created_at', [HelpersJson::startOfDay(), HelpersJson::endOfDay()])->count('id');
        //        $user_status = UserStatus::query()->where('id', $status_id)->select('amount_errors')->first();
        //        $sumtoerror  = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();
        //        $stars       = $user_status->amount_errors - $errorsdb;
        //        $stars_pro   = $sumtoerror->residue;
        //        $javoblar    = AdminService::questionAnswerToArrayShuffle($question->answer);
        //        shuffle($javoblar);
        //        unset($question->answer);
        //        $question->money  = HelpersJson::NumberFormat($question->money);
        //        $question->rating = HelpersJson::NumberFormat($question->rating);
        //        $errors           = $user_status->amount_errors - $errorsdb + $sumtoerror->obtained;
        //        if ($errors <= 0 && $stars_pro <= 0) {
        //            return HelpersJson::returnError(trans('all.test.all_errors_end'), [
        //                'title'    => trans('all.test.all_errors_end_title'),
        //                'type'     => 'error',
        //                'class'    => 'alert-danger',
        //                'back_url' => '/dashboard',
        //            ]);
        //        }
        //        //Test vaqtini o`chirb qo`yganligini aniqlaymiz
        //        $test_time_off = TimeOff::query()
        //            ->where('user_id', $user_id)
        //            ->where('status', 1)
        //            ->orderBy('ends', 'desc')
        //            ->select('ends', 'type')->get();
        //
        //        foreach ($test_time_off as $item) {
        //            //Agarda user sotib olgan vaqtni o`chirish funksiyasi vaqti tugagan bo`lsa uni deaktivatsiyalashtiramiz
        //            if ($item->ends < time()) {
        //                $item->update([
        //                    'status' => '0',
        //                ]);
        //            } else {
        //                if ($item->type === 'time') {
        //                    $timeof = true;
        //                }
        //                if ($item->type === 'star') {
        //                    $starof = true;
        //                }
        //            }
        //        }
        //
        //        return HelpersJson::returnSuccess(trans('all.test.title'), [
        //                'title'       => trans('all.test.title'),
        //                'user_rating' => HelpersJson::NumberFormat($user_rating),
        //                'user_money'  => HelpersJson::NumberFormat($user_money),
        //                'stars'       => ($stars > 0) ? $stars : 0,
        //                'stars_pro'   => $stars_pro,
        //                'timeof'      => isset($timeof),
        //                'starOf'      => isset($starof),
        //                'question'    => new QuestionTestViewModel($question),
        //                'answers'     => $javoblar,
        //            ]
        //        );

    }

    public function testCheck($request): array|string
    {
        if ((new ChampionshipService)->active() === true) {
            return HelpersJson::returnError(trans('all.chempionat_active'), [
                'title'    => trans('all.chempionat_active'),
                'type'     => 'error',
                'class'    => 'alert-error',
                'back_url' => '/dashboard',
            ]);
        }

        return QuestionService::testCheck(
            $request['answer'],
            auth()->user()->id,
            auth()->user()->status_id,
            auth()->user()->rating,
            auth()->user()->money,
            $request['question_id'],
            TestSessions::class
        );
    }

    public function bugSend($user_id, $name, $message, $question_id): array
    {
        $text_user = "@ITspeciallessons2 saytdagi testning xatoligi haqida xabar berishdi";
        $text_user .= "\nFoydalanuvchi nomi: ".$name;
        $text_user .= "\nFoydalanuvchi ID: ".$user_id;
        $text_user .= "\nSavol ID: ".$question_id;
        if (!empty($message)) {
            $text_user .= "\nXabar matni: ".$message;
        }
        $text_user .= "\n".json_encode(Question::query()->find($question_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $inline_keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Foydalanuvchi profili', 'url' => 'https://questa.uz/users/'.$user_id],
                ],
                [
                    ['text' => "Savolni ko'rish", 'url' => 'https://questa.uz/admin/question/view/'.$question_id],
                ],
            ],
        ];

        (new TelegramService)->sendMessage(env('ADMINS_GROUP_ID'), $text_user, 'HTML', 'false', null, $inline_keyboard);

        return AdminService::returnSuccess(['message' => 'Adminlarga yetkazildi']);
    }
}
