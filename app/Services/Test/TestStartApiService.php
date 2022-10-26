<?php

namespace App\Services\Test;

use App\Services\Helpers\HelpersJson;
use App\Models\TestSessions;
use App\Models\Question;
use App\Models\UserStatus;
use App\Models\PurchasedError;
use Auth;
use Illuminate\Support\Facades\DB;

class TestStartApiService
{
    public function index($request)
    {
        $user        = Auth::user();
        $answer      = $request['answer'];
        $questionid  = $request['id'];
        $user_id     = Auth::user()->id;
        $status_id   = Auth::user()->status_id;
        $user_rating = Auth::user()->rating;
        $user_money  = Auth::user()->money;

        if (Question::query()->where('id', $questionid)->select('id')->doesntExist()) {
            return HelpersJson::returnError("Okasi siz umuman mavjud bo`lmagan testga javob bermoqchisiz");
        }

        //DBdan ma'lumot olish
        $question    = Question::where('id', $questionid)->first();
        $javobmassiv = $question->answer;
        $javob       = HelpersJson::returnTrueAnswer($question->answer);
        $user_status = UserStatus::query()->where('id', $status_id)->first();
        $errorsdb    = TestSessions::query()->where('user_id', $user_id)->where('status', '0')
            ->whereBetween('created_at', [HelpersJson::startOfDay(), HelpersJson::endOfDay()])->count('status');
        $sumtoerror  = PurchasedError::where('user_id', $user_id)->select('residue', 'obtained')->first();
        $errors      = $user_status->amount_errors - $errorsdb + $sumtoerror->obtained;

        if (empty($answer)) {
            return HelpersJson::returnError("Siz umuman javob bermagansiz");
        }
        //Avval bu testga to`g`ri javob berganmi yo`qmi tekshiramiz. Avval to`g`ri javob bermagan bo`lsa yo`lida davom etadi
        if (TestSessions::query()->where('question_id', $questionid)->where('user_id', $user_id)->where('status', '1')->exists()) {
            return HelpersJson::returnError("Siz bu testni yechib bo`lgansiz. Adminga bu haqida xabar beildi keyngi testni yechishda davom eting");
        }

        //Agarda user umuman xato javob bergan bo`lsa unga bu xaqda aytamiz va yo`lini to`samiz
        if (!in_array($answer, $javobmassiv, true)) {
            return HelpersJson::returnError("siz umuman nomalum javobni berdingiz");
        }

        if ($errors <= 0 && $sumtoerror->residue <= 0) {
            return HelpersJson::returnError("Siz maksimum xato qilib boldingiz. Ertangi kunni kuting");
        }
        if ($javob !== $answer) {
            TestSessions::query()->insert([
                'user_id'     => $user_id,
                'question_id' => $questionid,
                'answer'      => $answer,
                'status'      => '0',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            if ($user_status->amount_errors <= $errorsdb && $sumtoerror->residue >= 0) {
                $ayir    = 1;
                $residue = $sumtoerror->residue - $ayir;
                PurchasedError::query()->where('user_id', '=', $user->id)->update(['residue' => $residue]);
            }

            return HelpersJson::returnError("false");
        }

        if ($javob === $answer) {
            $user_money_update  = $user_money + $question->money;
            $user_rating_update = $user_rating + $question->rating;
            $user->money        = $user_money_update;
            $user->rating       = $user_rating_update;
            $user->save();
            #Test session Tablega kiritib qo`yamiz
            TestSessions::query()->insert([
                'user_id'     => $user_id,
                'question_id' => $questionid,
                'answer'      => $answer,
                'status'      => '1',
            ]);

            DB::table('users')
                ->where('id', $user_id)
                ->update([
                    'question' => DB::raw('question + 1'),
                ]);

            return HelpersJson::returnSuccess("To`g`ri", [
                'new_ball'  => number_format((float)$user_rating_update, 2, '.', ''),
                'new_money' => number_format((float)$user_money_update, 2, '.', ''),
            ]);
        }

        return [
            'message' => 'Ok',
        ];
    }
}
