<?php
/**
 * Created by PhpStorm.
 * Filename: ExchangeService.php
 * Project Name: jwtlaravel.loc
 * User: Akbarali
 * Date: 10/09/2021
 * Time: 11:36
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */

namespace App\Services\Exchange;

use App\Models\PurchasedError;
use App\Models\TimeOff;
use App\Models\User;
use App\Services\Admin\AdminService;
use App\Services\Helpers\HelpersJson;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * Filename: ExchangeService.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 02/09/2022
 * Time: 2:17 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class ExchangeService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function getExchange(): array
    {
        $user          = Auth::user();
        $money         = number_format($user->money, '2', '.', '');
        $rating        = number_format($user->rating, '2', '.', '');
        $test_time_off = TimeOff::query()
            ->where('user_id', $user->id)
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

        return HelpersJson::returnSuccess(true, [
            'title'           => 'Haridlar bo`limi',
            'money'           => $money,
            'rating'          => $rating,
            'xatolik'         => $this->errorsAll(),
            'stars'           => $this->starsAll(),
            'test_time_off'   => $test_time_off,
            'time_block'      => isset($timeof),
            'star_block'      => isset($starof),
            'vaqtni_ochirish' => $this->timeOffAll(),
        ]);
    }

    //Pro Yulduzcha sotib olish
    public function exchangeStar($request): array
    {
        $user_id             = Auth::user()->id;
        $user_money          = Auth::user()->money;
        $telegram_id         = Auth::user()->telegram_id;
        $yulduzcha           = $request['number'];
        $sotib_olingan_error = PurchasedError::query()->where('user_id', $user_id)->first();

        $puli = match ((int)$yulduzcha) {
            1  => 30,
            11 => 310,
            20 => 580,
            35 => 970,
        };

        if ($user_money >= $puli) {
            $qolgan_pul = $user_money - $puli;
            User::query()->where('id', $user_id)->update([
                'money' => $qolgan_pul,
            ]);

            PurchasedError::query()->where('user_id', $user_id)->update([
                'user_id'     => $user_id,
                'residue'     => $sotib_olingan_error->residue + $yulduzcha,
                'obtained'    => $sotib_olingan_error->obtained + $yulduzcha,
                'total_error' => $sotib_olingan_error->total_error + $yulduzcha,
                'total_money' => $sotib_olingan_error->total_money + $puli,
            ]);

            DB::table('exchange_session')->insert(
                [
                    'user_id'     => $user_id,
                    'user_money'  => $user_money,
                    'total_money' => $qolgan_pul,
                    'rating'      => $yulduzcha,
                    'money'       => $puli,
                    'type'        => 'starAdd',
                    'created_at'  => date('Y-m-d H:i:s'),
                ]
            );

            if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
                $telegram_text = "Siz 🌟 sotib oldingiz.";
                $telegram_text .= "\nSiz sotib olgan yulduzcha miqdori: <b>".$yulduzcha."</b> 🌟 "."\n";
                $telegram_text .= "Sariflangan crypto: ".$puli." crypto\n";
                $telegram_text .= "Qolgan cryptoyingiz: ".$qolgan_pul." crypto";

                $this->telegramService->sendMessage($telegram_id, $telegram_text);
            }

            return HelpersJson::returnSuccess('Yulduzcha sotib olindi', ['balance_money' => HelpersJson::NumberFormat($qolgan_pul)]);
        }

        if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
            $this->telegramService->sendMessage($telegram_id, "Sizda yetarli Crypto miqdori yetarli bo'lmaganligi sababli 🌟 berilmadi.");
        }

        return HelpersJson::returnError(trans('all.crypo_yetarli_emas'));
    }

    //Test vaqtini o`chrib qo`yish
    public function exchangeTimeOff($request): array
    {
        $user_id     = Auth::user()->id;
        $user_money  = Auth::user()->money;
        $telegram_id = Auth::user()->telegram_id;
        $timeoff     = $request['number'];
        $hozrgivaqt  = time();

        $test_time_off = TimeOff::query()
            ->where('user_id', $user_id)
            ->where('type', 'time')
            ->where('status', 1)
            ->orderBy('ends', 'desc')
            ->select('ends')->first();
        if (isset($test_time_off) && $test_time_off->ends >= time()) {
            return HelpersJson::returnSuccess('Avvaldan aktivlashtirilgan.... Tugashini kuting!', ['balance_money' => HelpersJson::NumberFormat($user_money)]);
        }

        if ((int)$timeoff === 1) {
            $puli = 1000;
            $vaqt = 3600;
        } elseif ((int)$timeoff === 5) {
            $puli = 5000;
            $vaqt = 18000;
        }

        if ($user_money >= $puli) {
            $qolgan_pul = $user_money - $puli;
            User::query()->where('id', $user_id)->update([
                'money' => $qolgan_pul,
            ]);
            TimeOff::query()->insert(
                [
                    'user_id'  => $user_id,
                    'started'  => $hozrgivaqt,
                    'type'     => 'time',
                    'ends'     => $hozrgivaqt + $vaqt,
                    'obtained' => $timeoff,
                    'status'   => 1,
                ]
            );

            DB::table('exchange_session')->insert(
                [
                    'user_id'      => $user_id,
                    'user_money'   => $user_money,
                    'total_money'  => $user_money,
                    'total_rating' => $timeoff,
                    'money'        => $puli,
                    'type'         => 'timeOff',
                    'created_at'   => date('Y-m-d H:i:s'),
                ]
            );

            if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
                $telegram_text = "Siz test vaqtini o`chirb qo`yish funksiyasini sotib oldingiz.";
                $telegram_text .= "Siz sotib olgan vaqt: ".date('Y h.i', $vaqt)."\n";
                $telegram_text .= "Sariflangan crypto: ".HelpersJson::NumberFormat($puli)." crypto\n";
                $telegram_text .= "Qolgan cryptoyingiz: ".HelpersJson::NumberFormat($qolgan_pul)." crypto";

                $this->telegramService->sendMessage($telegram_id, $telegram_text);
            }

            return HelpersJson::returnSuccess('Test vaqti o\'chirib qo\'yildi', ['balance_money' => HelpersJson::NumberFormat($qolgan_pul)]);
        }
        if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
            $this->telegramService->sendMessage($telegram_id, "Sizda yetarli Crypto miqdori yetarli bo'lmaganligi sababli test vaqtini o'chirilmadi.");
        }

        return HelpersJson::returnError(trans('all.crypo_yetarli_emas'));
    }

    //Ball sotib olish
    public function exchangeBallAdd($request): array
    {
        $user_id     = Auth::user()->id;
        $user_money  = Auth::user()->money;
        $telegram_id = Auth::user()->telegram_id;
        $jami_bal    = $request['number'];
        $user_rating = Auth::user()->rating;
        $summa       = $jami_bal * 0.33;
        $jami_summa  = number_format((float)$summa, 3, '.', '');
        if ($user_money >= $jami_summa) {
            $qolgan_pul  = $user_money - $jami_summa;
            $qolgan_ball = $user_rating + $jami_bal;
            User::query()->where('id', $user_id)->update(
                [
                    'money'  => $qolgan_pul,
                    'rating' => $qolgan_ball,
                ]
            );

            DB::table('exchange_session')->insert(
                [
                    'user_id'      => $user_id,
                    'user_money'   => $user_money,
                    'total_money'  => $jami_summa,
                    'user_rating'  => $user_rating,
                    'total_rating' => $qolgan_ball,
                    'rating'       => $jami_bal,
                    'money'        => $summa,
                    'type'         => 'ballAdd',
                    'created_at'   => date('Y-m-d H:i:s'),
                ]
            );

            if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
                $telegram_text = "Siz bal sotib oldingiz.\n";
                $telegram_text .= "Siz sotib olgandingiz: ".$jami_bal." ball\n";
                $telegram_text .= "Hozirgi bal: ".HelpersJson::NumberFormat($qolgan_ball)." ball\n";
                $telegram_text .= "Sariflangan crypto: ".HelpersJson::NumberFormat($summa)." crypto\n";
                $telegram_text .= "Qolgan crypto: ".HelpersJson::NumberFormat($qolgan_pul)." crypto\n";

                $this->telegramService->sendMessage($telegram_id, $telegram_text);
            }

            return HelpersJson::returnSuccess(
                'Ballar sotib olindi',
                [
                    'balance_money'  => HelpersJson::NumberFormat($qolgan_pul),
                    'balance_rating' => HelpersJson::NumberFormat($qolgan_ball),
                ]
            );
        }
        if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
            $this->telegramService->sendMessage($telegram_id, "Sizda yetarli Crypto miqdori yetarli bo'lmaganligi sababli ballar berilmadi.");
        }

        return HelpersJson::returnError(trans('all.crypo_yetarli_emas'));
    }

    //Yulduzchalarni malum vaqtga cheksiz qilish
    public function exchangeStarOff($request, int $user_id, float $user_money, $telegram_id = null): array
    {
        $number = (int)$request['number'];
        $time   = time();

        $test_time_off = TimeOff::query()->where('user_id', $user_id)
            ->where('status', 1)
            ->where('type', 'star')
            ->orderBy('ends', 'desc')
            ->select('ends')->first();
        if (isset($test_time_off) && $test_time_off->ends >= $time) {
            if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
                $this->telegramService->sendMessage($telegram_id, "Avvaldan aktivlashtirilgan.... Tugashini kuting.");
            }

            return HelpersJson::returnSuccess('Avvaldan aktivlashtirilgan.... Tugashini kuting!', [
                'balance_money' => HelpersJson::NumberFormat($user_money),
            ]);
        }
        $find    = $this->starsAll($number);
        $money   = $find['money'];
        $time_if = $find['time'];
        if (isset($time_if, $money) && $user_money >= $money) {
            $money_new = $user_money - $money;
            $end_time  = $time + $time_if;
            User::query()->where('id', $user_id)->update([
                'money' => $money_new,
            ]);
            TimeOff::query()->insert(
                [
                    'user_id'  => $user_id,
                    'started'  => $time,
                    'type'     => 'star',
                    'ends'     => $end_time,
                    'obtained' => $number,
                    'status'   => 1,
                ]
            );

            DB::table('exchange_session')->insert(
                [
                    'user_id'      => $user_id,
                    'user_money'   => $user_money,
                    'total_money'  => $money_new,
                    'total_rating' => $number,
                    'money'        => $money,
                    'type'         => 'starOff',
                    'created_at'   => date('Y-m-d H:i:s'),
                ]
            );

            if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
                $telegram_text = "Sizda Yulduzchalarni cheksiz qilish Funksiyasi yoqildi.\n";
                $telegram_text .= "Siz sotib olgan vaqt: ".date('Y h.i', $time_if)." soat\n";
                $telegram_text .= "Tugash vaqti: ".AdminService::parseCarbonDateFormat($end_time)." soat\n";
                $telegram_text .= "Sariflangan crypto: ".HelpersJson::NumberFormat($money)." crypto\n";
                $telegram_text .= "Qolgan crypto: ".HelpersJson::NumberFormat($money_new)." crypto\n";
                $this->telegramService->sendMessage($telegram_id, $telegram_text);
            }

            return HelpersJson::returnSuccess("Yulduzchalkar cheksiz qilindi", ['balance_money' => HelpersJson::NumberFormat($money_new)]);
        }

        if ($telegram_id && $this->telegramService->sendingCheck($telegram_id)) {
            $this->telegramService->sendMessage($telegram_id, "Sizda Crypto yetarli bo'lmaganligi uchun Yulduzchalarni cheksiz qilish funksiyasi yoqilmadi.");
        }

        return HelpersJson::returnError(trans('all.crypo_yetarli_emas'));
    }

    //Yulduzchalar sotib olish uchun miqdorlar va narxlari
    private function errorsAll(): array
    {
        return [
            ['id' => 1, "dona" => 1, 'narxi' => 30],
            ['id' => 2, "dona" => 11, 'narxi' => 310],
            ['id' => 3, "dona" => 20, 'narxi' => 580],
            ['id' => 4, "dona" => 35, 'narxi' => 970],
        ];
    }

    private function timeOffAll(): array
    {
        return [
            '1' => 1000,
            '5' => 5000,
        ];
    }

    private function starsAll($find = null): array
    {
        $arr = [
            '2'  => 2000,
            '5'  => 6000,
            '12' => 12000,
            '24' => 20000,
        ];
        if (is_null($find)) {
            return $arr;
        }

        return [
            'time'  => (int)$find * 3600,
            'money' => $arr[$find],
        ];
    }
}
