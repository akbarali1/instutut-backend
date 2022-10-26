<?php
/**
 * Created by PhpStorm.
 * Filename: UserService.php
 * Project Name: vuejwtlaravel.loc
 * User: Akbarali
 * Date: 31/08/2021
 * Time: 5:33 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */

namespace App\Services\User;

use App\Filters\Question\DateBetweenFilter;
use App\Models\ChampionshipSessions;
use App\Models\ChampionshipSessionsModel;
use App\Models\PurchasedError;
use App\Models\TestSessions;
use App\Models\TimeOff;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use App\Services\Helpers\HelpersJson;
use App\Services\Question\QuestionService;
use App\Services\Telegram\TelegramService;
use App\ViewModels\User\UserQuestionModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\DB;

class UserService
{
    protected TelegramService $telegramService;

    public function __construct()
    {
        $this->telegramService = (new TelegramService());
    }

    public function UserStore($request): array
    {
        $user_id = Auth::user()->id;
        DB::table('users')->where('id', $user_id)->update([
            'last_name'      => $request->familiya,
            'name'           => $request->ism,
            'otasi'          => $request->ota,
            'dateo_of_birth' => [
                'oy'   => $request->oy,
                'sana' => $request->sana,
                'yil'  => $request->yil,
            ],
            'phone'          => $request->phone,
            'qiwi'           => $request->qiwi,
            'webmoney'       => $request->webmoney,
        ]);

        return HelpersJson::returnSuccess(trans('all.saving'));
    }

    public function getUsers($id): array
    {
        $user = User::query()->find($id);
        if (!$user) {
            return AdminService::returnError();
        }

        if ($photo = $user->photo) {
            $photo = route('welcome').$photo;
        }
        $admin = [];
        if (Auth::user()->rights > 1) {
            $stars_all = QuestionService::getUserStar($user->id, $user->status_id, true);

            if (!empty($user->dateo_of_birth)) {
                $oy   = $user->dateo_of_birth['oy'];
                $sana = $user->dateo_of_birth['sana'];
                $date = $sana.'.'.$oy.'.'.$user->dateo_of_birth['yil'];
            } else {
                $date = $user->year;
            }

            $admin = [
                'unique'      => $user->id_unquine,
                'money'       => AdminService::PrintNumberFormat($user->money),
                'stars'       => $stars_all,
                'email'       => $user->email,
                'telegram_id' => $user->telegram_id,
                'last_name'   => $user->last_name,
                'otasi'       => $user->otasi,
                'question'    => $user->question,
                'phone'       => $user->phone,
                'ban'         => $user->ban,
                'date'        => $date,
                'username'    => $user->username,

            ];
        }

        return AdminService::returnSuccess([
            'id'          => $user->id,
            'name'        => $user->name,
            'photo'       => $photo,
            'last_name'   => $user->last_name,
            'question'    => $user->question,
            'rating'      => $user->rating,
            'status_name' => $user->status_name,
            'status_id'   => $user->status_id,
            'admin'       => $admin,

        ]);


    }

    public function userRestore($id): array
    {
        $user = User::query()->find($id);
        if (!$user) {
            return AdminService::returnError();
        }
        $user->update([
            'money'           => 0,
            'status_id'       => 1,
            'status_name'     => 'Beginner',
            'question'        => 0,
            'purchased_error' => 0,
            'rating'          => 1,
        ]);

        $sotib_olingan = PurchasedError::query()->where('user_id', $id)->first();
        $sotib_olingan->update([
            'residue'  => 0,
            'obtained' => 0,
        ]);

        TestSessions::query()->where('user_id', $id)->delete();
        ChampionshipSessions::query()->where('user_id', $id)->delete();
        TimeOff::query()->where('user_id', $id)->delete();

        if ($user->telegram_id && $this->telegramService->sendingCheck($user->telegram_id)) {
            $text = "Sizningning barcha malumotlaringiz 0 ga tushirildi.";
            $this->telegramService->sendMessage($user->telegram_id, $text);
        }

        return AdminService::returnSuccess([
            'message' => 'Successfully restored',
        ]);
    }

    public function getUserTestsesion($id): array
    {
        if (User::query()->where('id', $id)->doesntExist()) {
            return AdminService::returnError();
        }

        if ((new ChampionshipService)->active()) {
            $query        = app(Pipeline::class)->send(
                ChampionshipSessionsModel::query()->where('user_id', $id)->with('question_name')
            )->through([
                DateBetweenFilter::class,
            ])->thenReturn();
            $test_session = $query->orderByDesc('championship_sessions.id')->paginate(AdminService::getPaginationLimit());
        } else {
            $query        = app(Pipeline::class)->send(
                TestSessions::query()->where('user_id', $id)->with('question_name')
            )->through([
                DateBetweenFilter::class,
            ])->thenReturn();
            $test_session = $query->orderByDesc('test_sessions.id')->paginate(AdminService::getPaginationLimit());
        }
        $test_session->appends(request()->all());

        $test_session->transform(function ($item) {
            return new UserQuestionModel($item);
        });

        return AdminService::returnSuccess($test_session);
    }

    public function web3Save(string $address, int $user_id): array
    {
        User::query()->where('id', $user_id)->update([
            'eth_address' => $address,
        ]);

        return AdminService::returnSuccess([
            'message' => 'Muvaffaqiyatli saqlandi',
        ]);

    }

    public function telegramSave($request, int $user_id): array
    {
        if (TelegramService::checkTelegramAuthorization($request)) {
            $telegram_id = (int)$request['id'];

            if ($this->telegramService->sendingCheck($telegram_id)) {
                $this->telegramService->connectedCongratulations($telegram_id);
            }

            User::query()->where('id', $user_id)->update([
                'telegram_id' => $telegram_id,
                'username'    => $request['username'] ?? null,
                'photo'       => isset($request['photo_url']) ? AdminService::saveFile($request['photo_url'], $user_id) : null,
            ]);

            return AdminService::returnSuccess([
                'message' => 'Telegram ulandi',
            ]);
        }

        return AdminService::returnError('Malumotlar yaroqsiz qayta harakat qiling');
    }

    public function userInfo(int $user_id): array
    {
        if ((new ChampionshipService)->active()) {
            $total_question_true  = ChampionshipSessions::query()->where('user_id', $user_id)->where('status', 1)->count();
            $total_question_false = ChampionshipSessions::query()->where('user_id', $user_id)->where('status', 0)->count();
            $today_question_true  = ChampionshipSessions::query()->where('user_id', $user_id)->where('status', 1)
                ->whereBetween(
                    'created_at',
                    [
                        HelpersJson::startOfDay(),
                        HelpersJson::endOfDay(),
                    ]
                )->count('status');
            $today_question_false = ChampionshipSessions::query()->where('user_id', $user_id)->where('status', 0)
                ->whereBetween(
                    'created_at',
                    [
                        HelpersJson::startOfDay(),
                        HelpersJson::endOfDay(),
                    ]
                )->count('status');
        } else {
            $total_question_true  = TestSessions::query()->where('user_id', $user_id)->where('status', 1)->count();
            $total_question_false = TestSessions::query()->where('user_id', $user_id)->where('status', 0)->count();
            $today_question_true  = TestSessions::query()->where('user_id', $user_id)->where('status', 1)
                ->whereBetween(
                    'created_at',
                    [
                        HelpersJson::startOfDay(),
                        HelpersJson::endOfDay(),
                    ]
                )->count('status');
            $today_question_false = TestSessions::query()->where('user_id', $user_id)->where('status', 0)
                ->whereBetween(
                    'created_at',
                    [
                        HelpersJson::startOfDay(),
                        HelpersJson::endOfDay(),
                    ]
                )->count('status');
        }

        $total_surfing_time = DB::table('surfing_sesions')->where('user_id', $user_id)->count();
        $today_surfing_time = DB::table('surfing_sesions')->where('user_id', $user_id)
            ->whereBetween(
                'created_at',
                [
                    HelpersJson::startOfDay(),
                    HelpersJson::endOfDay(),
                ]
            )->count();

        $start_pro = PurchasedError::query()->where('user_id', $user_id)->select(['residue', 'obtained', 'total_error', 'total_money'])->first();

        return AdminService::returnSuccess([
            'question'  => [
                'total' => [
                    'true'  => $total_question_true,
                    'false' => $total_question_false,
                ],
                'today' => [
                    'true'  => $today_question_true,
                    'false' => $today_question_false,
                ],
            ],
            'surfing'   => [
                'total' => $total_surfing_time,
                'today' => $today_surfing_time,
            ],
            'start_pro' => [
                'obtained' => $start_pro->obtained,
                'residue'  => $start_pro->residue,
                'total'    => $start_pro->total_error,
                'money'    => AdminService::PrintNumberFormat($start_pro->total_money),
            ],
        ]);
    }

    public function web3Disconnected(int $user_id): array
    {
        User::query()->where('id', $user_id)->update([
            'eth_address' => null,
        ]);

        return AdminService::returnSuccess([
            'message' => 'Muvaffaqiyatli o\'chirildi',
        ]);

    }

    public function telegramDisconnected(int $user_id): array
    {
        User::query()->where('id', $user_id)->update([
            'telegram_id' => null,
            'username'    => null,
            'photo'       => null,
        ]);

        return AdminService::returnSuccess([
            'message' => "Telegram muvaffaqiyatli o'chirildi",
        ]);

    }

    public function passwordChange(int $user_id, string $password): array
    {
        User::query()->where('id', $user_id)->update([
            'password' => bcrypt($password),
        ]);

        return AdminService::returnSuccess([
            'message' => 'Parol o\'zgartirildi',
        ]);
    }

    public function statusChange(int $user_id, int $status_id): array
    {
        $status    = UserStatus::query()->find($status_id);
        $find_user = User::query()->find($user_id);
        if ($find_user && $find_user->telegram_id && $this->telegramService->sendingCheck($find_user->telegram_id)) {
            $text = "Sizning statusingiz o'zgardi.";
            $text .= "\nStatus nomi: ".$status->name;
            $text .= "\n\nTabriklaymiz! 🎉🎉🎉";
            $this->telegramService->sendMessage($find_user->telegram_id, $text);
        }

        $find_user->update([
            'status_id'   => $status_id,
            'status_name' => $status->name,
        ]);

        return AdminService::returnSuccess([
            'message' => "Foydalanuvchi statusi o'zgardi",
        ]);
    }

    public function cryptoChange(int $user_id, $crypto): array
    {
        $user = User::query()->find($user_id);
        if (!$user) {
            return AdminService::QuestionNotFound('User not found');
        }
        $newMoney = (float)$user->money + $crypto;
        DB::table('users')->where('id', $user_id)->update([
            'money' => $newMoney,
        ]);

        if ($user->telegram_id && $this->telegramService->sendingCheck($user->telegram_id)) {
            $text = "Sizdagi Cyrpyo miqdori o'zgardi.\n";
            $text .= ($crypto < 0) ? "Cryptolaringizdan ayrildi: ".HelpersJson::NumberFormat($crypto) : "Cryptolaringizga qo'shildi: ".HelpersJson::NumberFormat($crypto);
            $text .= "\nHozirgi crypto miqdori: ".HelpersJson::NumberFormat($newMoney)." crypto\n";
            $this->telegramService->sendMessage($user->telegram_id, $text);
        }

        return AdminService::returnSuccess([
            'message' => 'Crypto yangilandi',
            'crypto'  => $newMoney,
        ]);
    }

    public function ratingChange(int $user_id, int $rating, bool $checkbox = false): array
    {
        $user = User::query()->find($user_id);
        if (!$user) {
            return AdminService::QuestionNotFound('User not found');
        }

        $newRating = (float)$user->rating + $rating;
        DB::table('users')->where('id', $user_id)->update([
            'rating' => $newRating,
        ]);
        $text = '';
        if ($user->telegram_id && $this->telegramService->sendingCheck($user->telegram_id)) {
            $text .= "Sizdagi Reyting miqdori o'zgardi.\n";
            $text .= ($rating < 0) ? "Reytingizdan ayrildi: ".HelpersJson::NumberFormat($rating) : "Reytingizga qo'shildi: ".HelpersJson::NumberFormat($rating);
            $text .= "\nHozirgi reyting miqdori: ".HelpersJson::NumberFormat($newRating)."\n";
        }

        if ($checkbox && (new ChampionshipService())->active()) {
            ChampionshipSessionsModel::query()->insert([
                'user_id'     => $user_id,
                'question_id' => 0,
                'answer'      => 'Akkauntiga adminkadan ball qo`shildi',
                'rating'      => $rating,
                'status'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            $minus   = ($rating < 0) ? "ayrildi" : "qo'shildi";
            $message = "Chempionat va userni reytingdan ".$minus;
        } else {
            $minus   = ($rating < 0) ? "ayrildi" : "qo'shildi";
            $message = "Faqat reytingdan ".$minus;
        }
        if ($user->telegram_id && $this->telegramService->sendingCheck($user->telegram_id)) {
            $text .= $message;
            $this->telegramService->sendMessage($user->telegram_id, $text);
        }

        return AdminService::returnSuccess([
            'message' => $message,
            'rating'  => $newRating,
        ]);
    }

    public function getReferalUser(int $user_id): array
    {
        $refer = User::query()->where('ref_id', $user_id)->select(['id', 'name', 'ref_bonus', 'telegram_id', 'created_at'])
            ->orderByDesc('ref_bonus')
            ->orderByDesc('created_at')
            ->paginate(20);
        $data  = [];

        foreach ($refer->items() as $key => $item) {
            $number = $refer->firstItem() + $key;
            $data[] = [
                'number'      => $number,
                'id'          => $item->id,
                'name'        => $item->name,
                'verified'    => ($item->ref_bonus === 1),
                'telegram_id' => !is_null($item->telegram_id),
                'data'        => AdminService::parseCarbonDateFormat($item['created_at']),
            ];
        }

        $refer         = $refer->toArray();
        $refer['data'] = $data;

        return AdminService::returnSuccess([
            'status' => 'success',
            'data'   => $refer,
        ]);
    }

    public function userBanned($user_id): array
    {
        if ((int)$user_id === 1) {
            return AdminService::returnError(['message' => 'Bu userni banlay olmaysiz u tanxo!']);
        }
        $user = User::query()->find($user_id);
        if (!$user) {
            return AdminService::QuestionNotFound('User not found');
        }

        if ($user->ban === 0) {
            $user->update(['ban' => 1]);
            $tg_message     = "Siz bloklandingiz.";
            $return_message = 'User banlandi va u endi saytga kira olmaydi';
        } else {
            $user->update(['ban' => 0]);
            $tg_message     = "Sizdagi bloklanish olib tashlandi.";
            $return_message = 'User banlangan ekan uning bani ochildi';
        }
        if ($user->telegram_id && $this->telegramService->sendingCheck($user->telegram_id)) {
            $tg_message .= "\nSavollaringiz bo'lsa https://t.me/questauz_online guruhimizda berishingiz mumkin.";

            $this->telegramService->sendMessage($user->telegram_id, $tg_message);
        }

        return AdminService::returnSuccess(['message' => $return_message]);
    }

    public function findUniqueId($unique_id): array
    {
        $unique_id = trim($unique_id);
        if (str_starts_with($unique_id, '#')) {
            $unique_id = substr($unique_id, 1);
        }
        $user = User::query()->where('id_unquine', '=', $unique_id)->first();
        if (!$user) {
            return AdminService::returnError(['message' => 'Bu Unikalniy ID bo`yicha Foydalanuvchi mavjud emas!']);
        }

        return AdminService::returnSuccess(['user' => $user]);
    }
}
