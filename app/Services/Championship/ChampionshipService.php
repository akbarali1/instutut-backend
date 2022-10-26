<?php

namespace App\Services\Championship;

use App\Models\ChampionshipModel;
use App\Models\ChampionshipSessionsModel;
use App\Services\Admin\AdminService;
use App\Services\Helpers\HelpersJson;
use App\Services\Question\QuestionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * Filename: ChampionshipService.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 02/08/2022
 * Time: 6:59 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class ChampionshipService
{
    public function getTest(): array
    {
        if ($this->active() !== true) {
            return AdminService::returnError('Championship is not active');
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
            'champion',
            ChampionshipSessionsModel::class
        );

        //        $question = QuestionService::getQuestion($user_id, $status_id, 'championship_sessions');
        //
        //        if (!is_object($question) && isset($question['error']) && $question['error'] === true) {
        //
        //            $response = [
        //                'title'    => 'Test umuman qolmagan',
        //                'type'     => 'error',
        //                'class'    => 'alert-success',
        //                'back_url' => '/dashboard',
        //            ];
        //
        //            return HelpersJson::returnError(trans('all.savollar_qolmadi'), $response);
        //        }
        //
        //        $errorsdb = ChampionshipSessionsModel::query()->where('user_id', $user_id)->where('status', '0')
        //            ->whereBetween('created_at', [HelpersJson::startOfDay(), HelpersJson::endOfDay()])->count('id');
        //
        //        $user_status = UserStatus::query()->where('id', $status_id)->select('amount_errors')->first();
        //        $sumtoerror  = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();
        //        $stars       = $user_status->amount_errors - $errorsdb;
        //        $stars_pro   = $sumtoerror->residue;
        //        $javoblar    = AdminService::questionAnswerToArrayShuffle($question->answer, true);
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
        //                'question'    => new ChampionshipViewModel($question),
        //                'answers'     => $javoblar,
        //            ]
        //        );

    }

    public function testCheck($request): array|string
    {
        if ($this->active() !== true) {
            return AdminService::returnError('Championship is not active');
        }

        return QuestionService::testCheck(
            $request['answer'],
            auth()->user()->id,
            auth()->user()->status_id,
            auth()->user()->rating,
            auth()->user()->money,
            $request['question_id'],
            ChampionshipSessionsModel::class
        );

        //        $user        = Auth::user();
        //        $answer      = $request['answer'];
        //        $questionid  = $request['question_id'];
        //        $user_id     = Auth::user()->id;
        //        $status_id   = Auth::user()->status_id;
        //        $user_rating = Auth::user()->rating;
        //        $user_money  = Auth::user()->money;

        //
        //        if (empty($answer)) {
        //            return HelpersJson::returnError("Siz umuman javob bermagansiz");
        //        }
        //        //Avval bu testga to`g`ri javob berganmi yo`qmi tekshiramiz. Avval to`g`ri javob bermagan bo`lsa yo`lida davom etadi
        //        if (ChampionshipSessionsModel::query()->where('question_id', $questionid)->where('user_id', $user_id)
        //            ->where('status', '1')->exists()) {
        //            TelegramService::AdminsNotification("Yechilgan testni yechmoqchi bo'lishdi \n https://questa.uz/users/".$user->id."\n\n Savol ID: ".$questionid."\n\n Javob: ".$answer);
        //
        //            return HelpersJson::returnError("Siz bu testni yechib bo`lgansiz. Adminga bu haqida xabar beildi keyngi testni yechishda davom eting");
        //        }
        //        $question = Question::query()->find($questionid);
        //
        //        if (!$question) {
        //            TelegramService::AdminsNotification("Umuman mavjud bo'lmagan testni yechmoqchi bo'lishdi \n https://questa.uz/users/".$user->id."\n\n Savol ID: ".$questionid."\n\n Javob: ".$answer);
        //
        //            return HelpersJson::returnError("Okasi siz umuman mavjud bo`lmagan testga javob bermoqchisiz");
        //        }
        //        //DBdan ma'lumot olish
        //        $javobmassiv = $question->answer;
        //
        //        $javob       = HelpersJson::returnTrueAnswer($question->answer);
        //        $user_status = UserStatus::query()->find($status_id);
        //        $errorsdb    = ChampionshipSessionsModel::query()->where('user_id', $user_id)->where('status', '0')->whereBetween(
        //            'created_at',
        //            [
        //                HelpersJson::startOfDay(),
        //                HelpersJson::endOfDay(),
        //            ]
        //        )->count('status');
        //
        //        $sumtoerror = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();
        //
        //        $errors   = $user_status->amount_errors - $errorsdb + $sumtoerror->obtained;
        //        $statusOf = TimeOff::query()->where('user_id', $user_id)
        //            ->where('status', 1)
        //            ->where('type', 'star')
        //            ->orderBy('ends', 'desc')
        //            ->select('ends')->first();
        //
        //        if ($statusOf && $statusOf->ends < time()) {
        //            $statusOf->update(
        //                [
        //                    'status' => '0',
        //                ]
        //            );
        //        }
        //
        //        if (!$statusOf && $errors <= 0 && $sumtoerror->residue <= 0) {
        //            return HelpersJson::returnError("Siz maksimum xato qilib boldingiz. Ertangi kunni kuting");
        //        }
        //
        //        if (trim($javob) !== trim($answer)) {
        //            if (!$statusOf) {
        //
        //                ChampionshipSessionsModel::query()->insert([
        //                    'user_id'     => $user_id,
        //                    'question_id' => $questionid,
        //                    'answer'      => $answer,
        //                    'status'      => '0',
        //                    'created_at'  => date('Y-m-d H:i:s'),
        //                ]);
        //
        //                if ($user_status->amount_errors <= $errorsdb && $sumtoerror->residue >= 0) {
        //                    PurchasedError::query()->where('user_id', $user->id)->decrement('residue');
        //                }
        //            }
        //
        //            return HelpersJson::returnError("false", [
        //                'new_ball'  => HelpersJson::NumberFormat($user_rating),
        //                'new_money' => HelpersJson::NumberFormat($user_money),
        //            ]);
        //
        //        }
        //
        //        //Agarda user umuman xato javob bergan bo`lsa unga bu xaqda aytamiz va yo`lini to`samiz
        //        if (!in_array(trim($answer), [trim($javobmassiv[0]), trim($javobmassiv[1]), trim($javobmassiv[2]), trim($javobmassiv[3])], true)) {
        //            TelegramService::AdminsNotification("Umuman nomalum javobni berilgan \n\n https://questa.uz/users/".$user->id."\n\n Savol ID: ".$questionid."\n\n Savolni o'zini ko'rish https://questa.uz/admin/question/view/".$questionid."\n\n Taxrirlash: https://questa.uz/admin/question/edit/".$questionid."\n\nJavob: ".$answer);
        //
        //            return HelpersJson::returnError("siz umuman nomalum javobni berdingiz");
        //        }
        //
        //        if (trim($javob) === trim($answer)) {
        //            $user_money_update  = $user_money + $question->money;
        //            $user_rating_update = $user_rating + $question->rating;
        //            $user->money        = $user_money_update;
        //            $user->rating       = $user_rating_update;
        //            $user->question++;
        //            $user->save();
        //            #Test session Tablega kiritib qo`yamiz
        //            ChampionshipSessionsModel::query()->insert([
        //                'user_id'     => $user_id,
        //                'question_id' => $questionid,
        //                'answer'      => $answer,
        //                'rating'      => $question->rating,
        //                'status'      => 1,
        //                'created_at'  => date('Y-m-d H:i:s'),
        //            ]);
        //
        //            return HelpersJson::returnSuccess("To`g`ri", [
        //                    'new_ball'  => HelpersJson::NumberFormat($user_rating_update),
        //                    'new_money' => HelpersJson::NumberFormat($user_money_update),
        //                ]
        //            );
        //        }
        //
        //        return 'not working';
    }

    public function all(): array
    {
        return AdminService::returnSuccess(ChampionshipModel::query()->orderByDesc('id')->get());
    }

    public function create($request): array
    {
        $data = $request->validated();
        unset($data['prices']);
        $championship = ChampionshipModel::query()->create($data);
        $awards       = [];
        $chem_awards  = '';
        if (count($request['prices']) > 0) {
            foreach ($request['prices'] as $award) {
                if (!is_null($award)) {
                    $awards[] = [
                        'chem_id'    => $championship->id,
                        'name'       => $award,
                        'created_at' => now(),
                    ];
                }
            }
            $chem_awards = DB::table('championship_awards')->insert($awards);
        }

        return AdminService::returnSuccess([
            'championship' => $championship,
            'awards'       => $chem_awards ?? '',
        ]);
    }

    public function update($request): array
    {
        $data = $request->validated();
        unset($data['prices']);
        $find = ChampionshipModel::query()->findOrFail($request->id);
        if (!$find) {
            return AdminService::QuestionNotFound('Chempionat not found');
        }
        $find->update($data);
        $awards = [];
        if (count($request['prices']) > 0) {
            foreach ($request['prices'] as $award) {
                if (!empty($award['name'])) {
                    if (isset($award['id']) && DB::table('championship_awards')->where('id', '=', $award['id'])->exists()) {
                        DB::table('championship_awards')->where('id', '=', $award['id'])
                            ->update(['name' => $award['name']]);
                    } else {
                        $awards[] = [
                            'chem_id'    => $data['id'],
                            'name'       => $award['name'],
                            'created_at' => now(),
                        ];
                    }
                }
            }
            DB::table('championship_awards')->insert($awards);
        }

        return AdminService::returnSuccess(
            [
                'message' => "Ma'lumot muvaffaqiyatli yangilandi",
            ]
        );
    }

    public function delete($id): array
    {
        $championship = ChampionshipModel::query()->find($id);
        if (!$championship) {
            return AdminService::returnError("Ma'lumot topilmadi");
        }
        $championship->delete();

        return AdminService::returnSuccess(
            [
                'message' => "Ma'lumot muvaffaqiyatli o'chirildi",
            ]
        );
    }

    public function getChampionshipById($id): array
    {
        $championship = ChampionshipModel::query()->find($id);
        if (!$championship) {
            return AdminService::returnError("Ma'lumot topilmadi");
        }

        return AdminService::returnSuccess(
            [
                'start_time'   => AdminService::changeTimeFormat($championship->start_time),
                'end_time'     => AdminService::changeTimeFormat($championship->end_time),
                'name'         => trim($championship->name),
                'id'           => $championship->id,
                'content'      => trim($championship->content),
                'count'        => $championship->count,
                'minimum_ball' => $championship->minimum_ball,
                'awards'       => DB::table('championship_awards')->where('chem_id', '=', $championship->id)->select(['id', 'name'])->get(),
            ]
        );
    }

    public function getActiveChampionship(): array
    {
        $day          = Carbon::now()->format('Y-m-d H:i:s');
        $championship = ChampionshipModel::query()->where('start_time', '>', $day)->first();
        if (!$championship) {
            if (ChampionshipModel::query()->where('end_time', '>', $day)->doesntExist()) {
                return AdminService::returnError("Ma'lumot topilmadi");
            }

            return AdminService::returnSuccess([
                'message' => "Hozirda aktiv chempionat bo'lmoqda",
            ]);
        }

        return AdminService::returnSuccess(
            [
                'start_time' => AdminService::changeTimeFormat($championship->start_time, 'Y-m-d H:i:s'),
                'end_time'   => AdminService::changeTimeFormat($championship->end_time, 'Y-m-d H:i:s'),
                'day'        => $day,
            ]
        );
    }

    public function getActiveChampionshipEndTime(): array
    {
        $day          = AdminService::getDateTime();
        $championship = ChampionshipModel::query()->where('end_time', '>', $day)->first();
        if (!$championship) {
            return AdminService::returnError(['message' => "Hozirda aktiv chempionat yo'q"]);
        }

        return AdminService::returnSuccess(
            [
                'id'         => $championship->id,
                'name'       => $championship->name,
                'content'    => $championship->content,
                'ball'       => $championship->minimum_ball,
                'count'      => $championship->count,
                'day'        => $day,
                'start_time' => AdminService::changeTimeFormat($championship->start_time, 'Y-m-d H:i:s'),
                'end_time'   => AdminService::changeTimeFormat($championship->end_time, 'Y-m-d H:i:s'),
            ]
        );
    }

    public function findChampionshipById($id): array
    {
        $championship = ChampionshipModel::query()->find($id);
        if (!$championship) {
            return AdminService::returnError(['message' => "Hozirda aktiv chempionat yo'q"]);
        }

        return AdminService::returnSuccess(
            [
                'id'         => $championship->id,
                'name'       => $championship->name,
                'content'    => $championship->content,
                'ball'       => $championship->minimum_ball,
                'count'      => $championship->count,
                'start_time' => AdminService::changeTimeFormat($championship->start_time, 'Y-m-d H:i:s'),
                'end_time'   => AdminService::changeTimeFormat($championship->end_time, 'Y-m-d H:i:s'),
            ]
        );
    }

    public function active(): bool
    {
        $day = AdminService::getDateTime();

        return ChampionshipModel::query()->where('end_time', '>', $day)->where('start_time', '<', $day)->exists();
    }

}
