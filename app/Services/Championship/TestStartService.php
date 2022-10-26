<?php

namespace App\Services\Test;

use App\Models\User;
use App\Services\Helpers\HelpersJson;
use App\Services\Question\QuestionService;
use Auth;
use App\Models\Question;
use App\Models\TimeOff;
use App\Models\TestSessions;
use App\Models\UserStatus;
use App\Models\PurchasedError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Created by PhpStorm.
 * Filename: TestStartService.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 19/08/2022
 * Time: 10:26 AM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class TestStartService
{
    public function getTest()
    {
        if (time() <= strtotime('2021-12-25 12:00:00')) {
            $response = [
                'title'    => 'Test umuman qolmagan',
                'type'     => 'error',
                'class'    => 'alert-success',
                'back_url' => '/dashboard',
            ];

            return HelpersJson::returnError('Test yechish vaqti xali boshlanmadi. Kuting tez orada boshlanadi', $response);
        }

        $user_id     = Auth::user()->id;
        $status_id   = Auth::user()->status_id;
        $user_rating = Auth::user()->rating;
        $user_money  = Auth::user()->money;

        $question = QuestionService::getQuestion($user_id, $status_id);

        if ($question) {
            $errorsdb    = TestSessions::query()->where('user_id', $user_id)->where('status', '0')->whereBetween('created_at', [HelpersJson::startOfDay(), HelpersJson::endOfDay()])->count('id');
            $user_status = UserStatus::query()->where('id', $status_id)->select('amount_errors')->first();
            $sumtoerror  = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();
            $stars       = $user_status->amount_errors - $errorsdb;
            $stars_pro   = $sumtoerror->residue;
            $photo_yes   = public_path('assets/images/tests/'.$question->id.'.png');
            $picture     = (file_exists($photo_yes)) ? url('/').'/public/assets/images/tests/'.$question->id.'.png' : false;
            $javoblar    = $question->answer;
            unset($question->answer);
            shuffle($javoblar);
            $question->money  = HelpersJson::NumberFormat($question->money);
            $question->rating = HelpersJson::NumberFormat($question->rating);
            $errors           = $user_status->amount_errors - $errorsdb + $sumtoerror->obtained;
            if ($errors <= 0 && $stars_pro <= 0) {
                return HelpersJson::returnError(trans('all.test.all_errors_end'), [
                    'title'    => trans('all.test.all_errors_end_title'),
                    'type'     => 'error',
                    'class'    => 'alert-danger',
                    'back_url' => '/dashboard',
                ]);
            }
            //Test vaqtini o`chirb qo`yganligini aniqlaymiz
            if (TimeOff::where('user_id', $user_id)->where('status', '1')->orderBy('ends', 'desc')->select('id')->exists()) {
                //Agarda test vaqtini o`chirib qo`ygan bo`lsa DBdan malumotlarini olamiz
                $test_time_off = TimeOff::where('user_id', $user_id)->orderBy('ends', 'desc')->where('status', '1')->select('ends')->first();
                //Agarda user sotib olgan vaqtni o`chirish funksiyasi vaqti tugagan bo`lsa uni deaktivatsiyalashtiramiz
                if ($test_time_off->ends < time()) {
                    TimeOff::where('user_id', $user_id)->where('status', '1')->update(
                        [
                            'status' => '0',
                        ]
                    );
                } else {
                    $timeof = [
                        'message' => trans('all.test_time_off'),
                        'type'    => 'success',
                    ];
                }
            }

            return HelpersJson::returnSuccess(trans('all.test.title'), [
                    'title'       => trans('all.test.title'),
                    'user_rating' => HelpersJson::NumberFormat($user_rating),
                    'user_money'  => HelpersJson::NumberFormat($user_money),
                    'stars'       => ($stars > 0) ? $stars : 0,
                    'stars_pro'   => $stars_pro,
                    'picture'     => $picture,
                    'timeof'      => isset($timeof),
                    'question'    => $question,
                    'answers'     => $javoblar,
                ]
            );

        } else {
            $response = [
                'title'    => 'Test umuman qolmagan',
                'type'     => 'error',
                'class'    => 'alert-success',
                'back_url' => '/dashboard',
            ];

            return HelpersJson::returnError(trans('all.savollar_qolmadi'), $response);
        }
    }

    public function testCheck($request)
    {
        $user        = Auth::user();
        $answer      = $request['answer'];
        $questionid  = $request['question_id'];
        $user_id     = Auth::user()->id;
        $status_id   = Auth::user()->status_id;
        $user_rating = Auth::user()->rating;
        $user_money  = Auth::user()->money;

        if (empty($answer)) {
            return HelpersJson::returnError("Siz umuman javob bermagansiz");
        }
        //Avval bu testga to`g`ri javob berganmi yo`qmi tekshiramiz. Avval to`g`ri javob bermagan bo`lsa yo`lida davom etadi
        if (TestSessions::where('question_id', $questionid)->where('user_id', $user_id)->where('status', '1')->exists()) {
            return HelpersJson::returnError("Siz bu testni yechib bo`lgansiz. Adminga bu haqida xabar beildi keyngi testni yechishda davom eting");
        }
        if (Question::where('id', $questionid)->select('id')->doesntExist()) {
            return HelpersJson::returnError("Okasi siz umuman mavjud bo`lmagan testga javob bermoqchisiz");
        } else {
            //DBdan ma'lumot olish
            $question    = Question::where('id', $questionid)->first();
            $javobmassiv = $question->answer;

            //Agarda user umuman xato javob bergan bo`lsa unga bu xaqda aytamiz va yo`lini to`samiz
            if (!in_array($answer, $javobmassiv)) {
                return HelpersJson::returnError("siz umuman nomalum javobni berdingiz");
            }

            $javob       = HelpersJson::returnTrueAnswer($question->answer);
            $user_status = UserStatus::where('id', $status_id)->first();
            $errorsdb    = TestSessions::where('user_id', $user_id)->where('status', '0')->whereBetween(
                'created_at',
                [
                    HelpersJson::startOfDay(),
                    HelpersJson::endOfDay(),
                ]
            )->count('status');

            $sumtoerror = PurchasedError::where('user_id', $user_id)->select('residue', 'obtained')->first();

            $errors = $user_status->amount_errors - $errorsdb + $sumtoerror->obtained;

            if ($errors <= 0 && $sumtoerror->residue <= 0) {
                return HelpersJson::returnError("Siz maksimum xato qilib boldingiz. Ertangi kunni kuting");
            }
            if (trim($javob) != trim($answer)) {
                TestSessions::insert([
                    'user_id'     => $user_id,
                    'question_id' => $questionid,
                    'answer'      => $answer,
                    'status'      => '0',
                ]);

                if ($user_status->amount_errors <= $errorsdb && $sumtoerror->residue >= 0) {
                    $ayir    = 1;
                    $residue = $sumtoerror->residue - $ayir;
                    PurchasedError::where('user_id', $user->id)->update(['residue' => $residue]);
                }

                return HelpersJson::returnError("false");

            } else {
                if (trim($javob) == trim($answer)) {
                    $user_money_update  = $user_money + $question->money;
                    $user_rating_update = $user_rating + $question->rating;
                    $user->money        = $user_money_update;
                    $user->rating       = $user_rating_update;
                    $user->question++;
                    $user->save();
                    #Test session Tablega kiritib qo`yamiz
                    TestSessions::insert([
                        'user_id'     => $user_id,
                        'question_id' => $questionid,
                        'answer'      => $answer,
                        'status'      => '1',
                    ]);

                    return HelpersJson::returnSuccess("To`g`ri", [
                            'new_ball'  => HelpersJson::NumberFormat($user_rating_update),
                            'new_money' => HelpersJson::NumberFormat($user_money_update),
                        ]
                    );
                }
            }
        }

        return 'not working';
    }
}
