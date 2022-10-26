<?php
/**
 * Question Service
 * @author: Akbarali
 * @date  :   2016-06-21
 */

namespace App\Services\Question;

use App\Filters\Question\AnswerFilter;
use App\Filters\Question\NameFilter;
use App\Filters\Question\TypeFilter;
use App\Models\ChampionshipSessions;
use App\Models\PurchasedError;
use App\Models\Question;
use App\Models\TestSessions;
use App\Models\TimeOff;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use App\Services\Helpers\HelpersJson;
use App\Services\Telegram\TelegramService;
use App\ViewModels\Question\ChampionshipViewModel;
use App\ViewModels\Question\QuestionTestViewModel;
use App\ViewModels\Question\QuestionViewModel;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionService
{

    public function index()
    {
        $questions = app(Pipeline::class)->send(
            Question::query()->where('status', 1)
        )->through(
            [
                TypeFilter::class,
                NameFilter::class,
                AnswerFilter::class,
            ]
        )->thenReturn()
            ->orderBy('id', 'desc')
            ->paginate(AdminService::getPaginationLimit())
            ->appends(request()->query());

        $questions->transform(function ($value) {
            return new QuestionViewmodel($value);
        });

        return AdminService::returnSuccess($questions);
    }

    public static function getQuestion(int $user_id, int $status_id, string $not_table_name = 'test_sessions', $not_text_question = false)
    {
        //toza SQL query
        //        $sql      = 'select `id`, `question`, `answer`,`money`, `time`, `rating`, `file`, `type` from `question` where `status_id` = '.$status_id.' and `deleted_at` is null and `id` not in ( select `question_id` from `'.$not_table_name.'` where `user_id` = '.$user_id.' and `status` = "1" and `deleted_at` is null ) order by RAND() limit 1';
        //        $question = DB::select($sql);
        //        $question = $question[0];
        //
        $question = Question::query()->where('status', 1)
            //                    ->where('status_id', $status_id)'
            //Faqat rasmli testlarni olish
            //->where('type', '=', '1')
            //            ->where('type', '=', '3')
            //Faqat rasmli fa ovozli testlarni olish
            //            ->whereIn('type', [1, 3])
            ->whereNotIn('id', function ($query) use ($user_id, $not_table_name) {
                $query->select('question_id')
                    ->where('user_id', $user_id)
                    ->where('status', 1)
                    //->whereNull('deleted_at')
                    ->from($not_table_name);
            })
            ->inRandomOrder()
            ->select(['id', 'question', 'answer', 'time', 'money', 'rating', 'file', 'type'])->first();
        //Joinda olish
        //        $question = Question::query()->where('question.status', '=', 1)
        //            ->join($not_table_name, 'question.id', '!=', $not_table_name.'.question_id')
        //            ->where($not_table_name.'.status', '=', 1)
        //            ->whereNull($not_table_name.'.deleted_at')
        //            //            ->where('question.type', '=', 3)
        //            ->inRandomOrder()
        //            ->select([
        //                'question.id',
        //                'question.question',
        //                'question.answer',
        //                'question.time',
        //                'question.money',
        //                'question.rating',
        //                'question.file',
        //                'question.type',
        //            ])->first();
        if (!$question) {
            return AdminService::returnError('No questions');
        }
        $question->file = AdminService::getFile($question->file, $question->id, $question->type);

        return $question;
    }

    public static function getUserStar($user_id, $status_id, $array_star = false)
    {
        $query       = (new ChampionshipService)->active() ? ChampionshipSessions::query() : TestSessions::query();
        $errors      = $query->where('user_id', $user_id)->where('status', '0')
            ->whereBetween('created_at', [HelpersJson::startOfDay(), HelpersJson::endOfDay()])->count('status');
        $user_status = UserStatus::query()->where('id', $status_id)->select('amount_errors')->first();
        $stars_pro   = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();

        if (!$stars_pro) {
            PurchasedError::query()->insert([
                'user_id'     => $user_id,
                'residue'     => 0,
                'obtained'    => 0,
                'total_error' => 0,
                'total_money' => 0,
            ]);
            $stars_pro = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();

        }
        $pro = $stars_pro->residue ?? 0;

        //Xatolar sonini yangilab qo`yamiz
        if (PurchasedError::query()->where('user_id', $user_id)->where('time', date('Y-m-d', time()))->select('id')->doesntExist()) {
            if ($pro <= $stars_pro->obtained ?? 0) {
                PurchasedError::query()->where('user_id', '=', $user_id)
                    ->update([
                        'obtained' => $pro,
                    ]);
            }
        }
        $stars = $user_status->amount_errors - $errors;

        if ($array_star) {
            return [
                'stars' => $stars,
                'pro'   => $pro,
            ];

        }


        return $stars + $pro;
    }

    public function trueAnswer($id): array
    {
        if (Question::query()->where('id', $id)->doesntExist()) {
            return AdminService::QuestionNotFound();
        }
        $questions = Question::query()->find($id);

        return AdminService::returnSuccess([
            'answer' => $questions->answer[0],
        ]);
    }

    public function questionsFiles(string $type = 'image')
    {
        $questions = match ($type) {
            'image' => Question::query()->orderBy('id', 'desc')->where('status', 1)
                ->where('file', '!=', '')
                ->where('type', '=', AdminService::getQuestionTypeNumber('image'))
                ->paginate(AdminService::getPaginationLimit()),
            'audio' => Question::query()->orderBy('id', 'desc')->where('status', 1)
                ->where('file', '!=', '')
                ->where('type', '=', AdminService::getQuestionTypeNumber('audio'))
                ->paginate(AdminService::getPaginationLimit()),
            default => Question::query()->orderBy('id', 'desc')->where('status', 1)
                ->where('file', '!=', '')->paginate(AdminService::getPaginationLimit()),
        };
        $questions->transform(function ($value) {
            return new QuestionViewmodel($value);
        });

        return AdminService::returnSuccess($questions);
    }

    public function excel()
    {
        $questions = Question::query()->orderBy('id', 'desc')->where('status', 2)->paginate(AdminService::getPaginationLimit());

        $questions->transform(function ($value) {
            return new QuestionViewmodel($value);
        });

        return AdminService::returnSuccess($questions);
    }

    public function getQuestionById($id): array
    {
        if (Question::query()->where('id', $id)->doesntExist()) {
            return AdminService::QuestionNotFound();
        }
        $question = Question::query()->find($id);

        return AdminService::returnSuccess(new QuestionViewmodel($question));
    }

    public function updateQuestion($request, $type = 'image'): array
    {
        if (Question::query()->where('id', $request->id)->doesntExist()) {
            return AdminService::QuestionNotFound();
        }
        if ($request->hasFile('files')) {
            $file_name = $request->file('files')->getClientOriginalName();
            $ext       = $request->file('files')->getClientOriginalExtension();
            $this->saveFile($request, $request->id, $type);
        } else {
            $file_name = $request->picture_name ?? '';
        }
        $javoblar = json_decode($request->answers);
        Question::query()->where("id", $request->id)->update(
            [
                'question'  => trim($request->name),
                'answer'    => [
                    trim($javoblar->answer1),
                    trim($javoblar->answer2),
                    trim($javoblar->answer3),
                    trim($javoblar->answer4),
                ],
                'money'     => trim($request->money),
                'rating'    => trim($request->rating),
                'time'      => trim($request->time),
                'file'      => trim($file_name),
                'type'      => isset($ext) ? AdminService::extFindId($ext) : 0,
                'status_id' => $request->status_id,
            ]
        );

        return AdminService::returnSuccess(['message' => 'Malumotlar yangilandi', 'id' => $request->id]);
    }

    public function deleteQuestion($id): array
    {
        if (Question::query()->where('id', $id)->doesntExist()) {
            return AdminService::QuestionNotFound();
        }
        Question::destroy($id);

        return AdminService::returnSuccess(['message' => 'Malumot o\'chirildi']);
    }

    public function uploadExcel($request): array
    {
        $document      = $request->file('files');
        $inputFileType = 'Xlsx';
        $inputFileName = $document->getRealPath();

        /**  Create a new Reader of the type defined in $inputFileType  **/
        $reader = IOFactory::createReader($inputFileType);
        /**  Advise the Reader that we only want to load cell data  **/
        $reader->setReadDataOnly(true);
        /**  Load $inputFileName to a Spreadsheet Object  **/
        $spreadsheet = $reader->load($inputFileName);
        $sheetData   = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $worksheet   = $spreadsheet->getSheet(0);                                                                                                                                                                                                                                                                                                                                                                                                                                                        //
        // Get the highest row and column numbers referenced in the worksheet
        $highestRow         = $worksheet->getHighestRow(
        );                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               // e.g. 10
        $highestColumn      = $worksheet->getHighestColumn(
        );                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               // e.g 'F'
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $data = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $riga = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $riga[] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
            if (1 === $row) {
                $keys = $riga;
                continue;
            }
            $data[] = array_combine($keys, $riga);
        }

        return $this->saveExcel($data);
    }

    public function saveExcel($data): array
    {
        $question = [];
        foreach ($data as $value) {
            if (!array_key_exists('name', $value) || !array_key_exists('answer1', $value)) {
                return AdminService::returnError([
                    'message' => 'Excel noto\'g\'ri formatda',
                    'html'    => '<a href="https://t.me/c/1205306591/17096" target="_blank">Yuklab olish uchun bosing</a>',
                ]);
            }
            if (!is_null($value['name'])) {
                $question[] = [
                    'question'  => $value['name'],
                    'answer'    => json_encode([
                        trim($value['answer1']),
                            trim($value['answer2']) ?? trans('all.question_null'),
                            trim($value['answer3']) ?? trans('all.question_null'),
                            trim($value['answer4']) ?? trans('all.question_null'),
                    ]),
                    'money'     => $value['money'] ?? 1,
                    'rating'    => $value['rating'] ?? 1,
                    'time'      => $value['time'] ?? 60,
                    'status_id' => $value['status'] ?? 1,
                    'file'      => '',
                    'status'    => 2,
                ];
            }
        }
        Question::query()->insert($question);

        return AdminService::returnSuccess(['message' => 'Malumotlar saqlandi']);
    }

    public function sendCheck($id): array
    {
        Question::query()->whereIn('id', $id)
            ->update(
                [
                    'status' => 1,
                ]
            );

        return AdminService::returnSuccess(['message' => 'Belgilanganlar tasdiqlandi']);
    }

    public function cancelCheck($id): array
    {
        Question::query()->whereIn('id', $id)
            ->update(
                [
                    'deleted_at' => now(),
                ]
            );

        return AdminService::returnSuccess(['message' => 'Belgilanganlar bekor qilindi']);
    }

    public function questionCreate($request, $type = 'image'): array
    {
        $type_id    = AdminService::getQuestionTypeNumber($type);
        $image_name = $request->hasFile('files') ? $request->file('files')->getClientOriginalName() : '';
        $vocabulary = json_decode($request->answers);
        $data       = Question::query()->create(
            [
                'question'  => $request->name,
                'answer'    => [
                    trim($vocabulary->answer1),
                        trim($vocabulary->answer2) ?? trans('all.question_null'),
                        trim($vocabulary->answer3) ?? trans('all.question_null'),
                        trim($vocabulary->answer4) ?? trans('all.question_null'),
                ],
                'money'     => $request->money,
                'rating'    => $request->rating,
                'time'      => $request->time,
                'file'      => $image_name,
                'type'      => $type_id,
                'status_id' => $request->status_id,
                'status'    => 2,
            ]
        );

        if ($request->hasFile('files')) {
            $saveImage = $this->saveFile($request, $data->id, $type);
        }

        return AdminService::returnSuccess(
            [
                'id'      => $data->id,
                'message' => 'Malumotlar saqlandi',
                'file'    => $saveImage ?? '',
            ]
        );
    }

    public function saveFile($request, $id, $type)
    {
        if ($files = $request->file('files')) {
            switch ($type) {
                case 'image':
                    $destinationPath = public_path('/assets/images/tests/');
                    break;
                case 'audio':
                    $destinationPath = public_path('/assets/audios/tests/');
                    break;
                case 'video':
                    $destinationPath = public_path('/assets/videos/tests/');
                    break;
                case 'users':
                    $destinationPath = public_path('/assets/images/users/');
                    break;

            }

            if (isset($destinationPath)) {
                $profileImage = $id.".".$files->getClientOriginalExtension();
                $statuses     = $files->move($destinationPath, $profileImage);

                return $files->getClientOriginalName();
            }

            return AdminService::returnError(['message' => 'Faylni yuklashda xatolik']);

        }
    }

    public function info(): array
    {
        $question    = Question::query()->where('status', '=', 1);
        $image_count = clone $question;

        return AdminService::returnSuccess(
            [
                'count'       => $question->count(),
                'image_count' => $image_count->where('file', '!=', '')
                    ->where('type', '!=', AdminService::getQuestionTypeNumber('audio'))->count(),
                'money'       => AdminService::getPrintNumberFormat($question->sum('money')),
                'rating'      => AdminService::getPrintNumberFormat($question->sum('rating')),
            ]
        );
    }

    public static function testCheck($answer, $user_id, $status_id, $user_rating, $user_money, $questionId, $modalName = TestSessions::class): array|string
    {
        if (empty($answer)) {
            return HelpersJson::returnError("Siz umuman javob bermagansiz");
        }
        $answer = trim($answer);
        //Avval bu testga to`g`ri javob berganmi yo`qmi tekshiramiz. Avval to`g`ri javob bermagan bo`lsa yo`lida davom etadi
        if ($modalName::query()->where('question_id', '=', $questionId)->where('user_id', '=', $user_id)->where('status', '=', 1)->exists()) {
            TelegramService::AdminsNotification("Yechilgan testni yechmoqchi bo'lishdi \n https://questa.uz/users/".$user_id."\n\n Savol ID: ".$questionId."\n\n Javob: ".$answer);

            return HelpersJson::returnError("Siz bu testni yechib bo`lgansiz. Adminga bu haqida xabar beildi keyngi testni yechishda davom eting");
        }
        $question = Question::query()->find($questionId);

        if (!$question) {
            TelegramService::AdminsNotification("Umuman mavjud bo'lmagan testni yechmoqchi bo'lishdi \n https://questa.uz/users/".$user_id."\n\n Savol ID: ".$questionId."\n\n Javob: ".$answer);

            return HelpersJson::returnError("Okasi siz umuman mavjud bo`lmagan testga javob bermoqchisiz");
        }
        //DBdan ma'lumot olish
        $answerArray = $question->answer;

        $javob       = HelpersJson::returnTrueAnswer($question->answer);
        $user_status = UserStatus::query()->find($status_id);
        $errorsdb    = $modalName::query()->where('user_id', $user_id)->where('status', 0)->whereBetween(
            'created_at',
            [
                HelpersJson::startOfDay(),
                HelpersJson::endOfDay(),
            ]
        )->count('status');

        $sumtoerror = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();

        $errors   = $user_status->amount_errors - $errorsdb + $sumtoerror->obtained;
        $statusOf = TimeOff::query()->where('user_id', $user_id)
            ->where('status', '=', '1')
            ->where('type', 'star')
            ->orderBy('ends', 'desc')
            ->select('id', 'ends')->first();

        if ($statusOf && $statusOf->ends < time()) {
            DB::table('test_time_off')->where('id', '=', $statusOf->id)->update([
                'status' => '0',
            ]);
            DB::table('test_time_off')->where('id', '=', $statusOf->id)
                ->where('status', '=', 0)->delete();
            $statusOf = TimeOff::query()->where('user_id', $user_id)
                ->where('status', '=', 1)
                ->where('type', 'star')
                ->orderBy('ends', 'desc')
                ->select('id', 'ends')->first();
        }

        if (!$statusOf && $errors <= 0 && $sumtoerror->residue <= 0) {
            return HelpersJson::returnError("Siz maksimum xato qilib boldingiz. Ertangi kunni kuting");
        }

        //Xato javob bergan bo`lsa
        if ($javob !== $answer) {
            return (new self)->errorTestAnswer($modalName, $user_id, $questionId, $answer, $statusOf, $sumtoerror, $errorsdb, $user_status, $user_rating, $user_money);
        }

        //Agarda user umuman xato javob bergan bo`lsa unga bu xaqda aytamiz va yo`lini to`samiz
        if (!in_array($answer, $answerArray)) {
            $text_not_question = 'Umuman nomalum javobni berilgan';
            $text_not_question .= "\n\nhttps://questa.uz/users/".$user_id;
            $text_not_question .= "\n\nUsername: ".auth()->user()->name;
            $text_not_question .= "\n\nSavol ID: ".$questionId;
            $text_not_question .= "\n\nSavolni o'zini ko'rish https://questa.uz/admin/question/view/".$questionId;
            $text_not_question .= "\n\nTaxrirlash: https://questa.uz/admin/question/edit/".$questionId;
            $text_not_question .= "\n\nJavob: <b>\"".$answer."\"</b>";
            $text_not_question .= "\n\n ".json_encode($question, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            TelegramService::AdminsNotification($text_not_question);

            return (new self)->errorTestAnswer($modalName, $user_id, $questionId, $answer, $statusOf, $sumtoerror, $errorsdb, $user_status, $user_rating, $user_money);
        }

        $user_money_update  = $user_money + $question->money;
        $user_rating_update = $user_rating + $question->rating;

        $newUser = User::query()->find($user_id);
        if (!$newUser) {
            return AdminService::QuestionNotFound('User not found');
        }
        $newUser->update([
            'money'    => $user_money_update,
            'rating'   => $user_rating_update,
            'question' => $newUser->question++,
        ]);
        #Test session Tablega kiritib qo`yamiz
        $modalName::query()->insert([
            'user_id'     => $user_id,
            'question_id' => $questionId,
            'answer'      => $answer,
            'rating'      => $question->rating,
            'status'      => 1,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return HelpersJson::returnSuccess("To`g`ri", [
                'new_ball'  => HelpersJson::NumberFormat($user_rating_update),
                'new_money' => HelpersJson::NumberFormat($user_money_update),
            ]
        );
    }

    protected function errorTestAnswer($modalName, $user_id, $questionId, $answer, $statusOf, $sumtoerror, $errorsdb, $user_status, $user_rating, $user_money): array
    {
        $modalName::query()->insert([
            'user_id'     => $user_id,
            'question_id' => $questionId,
            'answer'      => $answer,
            'status'      => $statusOf ? 2 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        if ($user_status->amount_errors <= $errorsdb && $sumtoerror->residue >= 0) {
            PurchasedError::query()->where('user_id', $user_id)->decrement('residue');
        }

        return HelpersJson::returnError("false", [
            'new_ball'  => HelpersJson::NumberFormat($user_rating),
            'new_money' => HelpersJson::NumberFormat($user_money),
        ]);

    }

    public static function getTest($user_id, $status_id, $user_rating, $user_money, $type, $modalName = TestSessions::class): array
    {
        $not_table = $modalName === TestSessions::class ? 'test_sessions' : 'championship_sessions';

        $question = self::getQuestion($user_id, $status_id, $not_table);

        if (!is_object($question) && isset($question['error']) && $question['error'] === true) {
            $response = [
                'title'    => 'Test umuman qolmagan',
                'type'     => 'error',
                'class'    => 'alert-success',
                'back_url' => '/dashboard',
            ];

            return HelpersJson::returnError(trans('all.savollar_qolmadi'), $response);
        }

        $errorsdb = $modalName::query()->where('user_id', $user_id)->where('status', '0')
            ->whereBetween('created_at', [HelpersJson::startOfDay(), HelpersJson::endOfDay()])->count('id');

        $user_status = UserStatus::query()->where('id', $status_id)->select('amount_errors')->first();
        $sumtoerror  = PurchasedError::query()->where('user_id', $user_id)->select('residue', 'obtained')->first();
        $stars       = $user_status->amount_errors - $errorsdb;
        $stars_pro   = $sumtoerror->residue;
        $javoblar    = AdminService::questionAnswerToArrayShuffle($question->answer, true);
        shuffle($javoblar);
        unset($question->answer);
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
        $test_time_off = TimeOff::query()
            ->where('user_id', $user_id)
            ->where('status', 1)
            ->orderBy('ends', 'desc')
            ->select('ends', 'type')->get();

        foreach ($test_time_off as $item) {
            //Agarda user sotib olgan vaqtni o`chirish funksiyasi vaqti tugagan bo`lsa uni deaktivatsiyalashtiramiz
            if ($item->ends < time()) {
                $item->update([
                    'status' => '0',
                ]);
            } else {
                if ($item->type === 'time') {
                    $timeof = true;
                }
                if ($item->type === 'star') {
                    $starof = true;
                }
            }
        }

        return HelpersJson::returnSuccess(trans('all.test.title'), [
                'title'       => trans('all.test.title'),
                'user_rating' => HelpersJson::NumberFormat($user_rating),
                'user_money'  => HelpersJson::NumberFormat($user_money),
                'stars'       => ($stars > 0) ? $stars : 0,
                'stars_pro'   => $stars_pro,
                'timeof'      => isset($timeof),
                'starOf'      => isset($starof),
                'question'    => ($type === 'test') ? new QuestionTestViewModel($question) : new ChampionshipViewModel($question),
                'answers'     => $javoblar,
            ]
        );
    }

}
