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

namespace App\Services\Surfing;

use App\Models\ChampionshipSessions;
use App\Models\ChampionshipSessionsModel;
use App\Models\PurchasedError;
use App\Models\SurfingModel;
use App\Models\TestSessions;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use App\Services\Helpers\HelpersJson;
use App\ViewModels\User\RatingUserViewModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SurfingService
{

    public function create($request)
    {
        $user_id = Auth::user()->id;

        $surfing = SurfingModel::query()->create([
            'user_id'     => $user_id,
            'name'        => $request->name,
            'site_url'    => $request->site_url,
            'description' => $request->description,
            'rating'      => $request->rating,
            'money'       => $request->money,
            'time'        => $request->time,
            'error'       => $request->error,
        ]);


        return AdminService::returnSuccess($surfing);
    }

    public function update($data)
    {
        $surfing = SurfingModel::query()->find($data['id']);
        if (!$surfing) {
            return AdminService::QuestionNotFound('Surfing not found');
        }
        $surfing->update($data);

        return AdminService::returnSuccess(['message' => 'Surfing yangilandi']);

    }

    public function end($id)
    {
        $user_id     = Auth::user()->id;
        $user_money  = Auth::user()->money;
        $user_rating = Auth::user()->rating;
        $status_id   = Auth::user()->status_id;
        //                if (DB::table('surfing_sesions')->where('surfing_id', $id)->where('user_id', $user_id)->exists()) {
        //                    return AdminService::returnError(['message' => 'Surfing not found']);
        //                }
        $PurchasedError = PurchasedError::query()->where('user_id', $user_id)->first();
        $error_amount   = UserStatus::query()->where('id', $status_id)->first();
        if ($PurchasedError->residue >= $error_amount->amount_errors) {
            return AdminService::returnSuccess(['message' => 'Muvaffaqqiyatli ko\'rildi']);
        }

        DB::beginTransaction();
        try {
            $surfing = SurfingModel::query()->find($id);
            //                $sotib_olingan_error = PurchasedError::query()->find($user_id);
            $yulduzcha = $surfing->error;

            DB::table('surfing_sesions')->insert([
                'user_id'    => $user_id,
                'surfing_id' => $id,
                'end_time'   => date('Y-m-d H:i:s'),
            ]);

            $user = User::query()->find($user_id);

            $user->update([
                'money'  => $user_money + $surfing->money,
                'rating' => $user_rating + $surfing->rating,
            ]);

            $championshipActive = (new ChampionshipService())->active();
            if ($surfing->rating !== 0 && $championshipActive) {
                ChampionshipSessionsModel::query()->insert([
                    'user_id'     => $user_id,
                    'question_id' => 0,
                    'answer'      => 'Surfing ko`rgani uchun berildi',
                    'rating'      => $surfing->rating,
                    'status'      => 1,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }

            // Pro Yulduz qo`shish
            //                PurchasedError::where('user_id', $user_id)->update([
            //                    'user_id' => $user_id,
            //                    'residue' => $sotib_olingan_error->residue + $yulduzcha,
            //                    'obtained' => $sotib_olingan_error->obtained + $yulduzcha,
            //                    'total_error' => $sotib_olingan_error->total_error + $yulduzcha,
            //                ]);
            //Bitta yulduzcha qo'shish

            if ($yulduzcha !== 0) {
                $TestSessions = $championshipActive ? ChampionshipSessions::query() : TestSessions::query();
                $TestSessions->where('user_id', $user_id)
                    ->where('status', '=', 0)->whereBetween('created_at', [
                        HelpersJson::startOfDay(),
                        HelpersJson::endOfDay(),
                    ])->first();
                if (!$TestSessions) {
                    if ($surfing->rating !== 0) {
                        return AdminService::returnSuccess(['message' => 'Sizga faqat reyting berildi']);
                    }
                    if ($surfing->money !== 0) {
                        return AdminService::returnSuccess(['message' => 'Sizga faqat crypto berildi']);
                    }

                    return AdminService::returnError(['message' => 'Siz maksimum yulduzlar mavjud']);
                }
                $TestSessions->delete();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            return AdminService::returnError($e->getMessage());
        }

        return AdminService::returnSuccess([
            'message' => 'Mukofotlar berildi',
        ]);
    }

    public function getSurfingById($id): array
    {
        $surfing = SurfingModel::query()->select(['id', 'name', 'site_url', 'time'])->find($id);
        if (!$surfing) {
            return AdminService::returnError('Surfing not found');
        }

        return AdminService::returnSuccess($surfing);
    }

    public function delete($surfing_id): array
    {
        SurfingModel::query()->find($surfing_id)->delete();

        return AdminService::returnSuccess([
            'message' => "Ma'lumot muvaffaqiyatli o'chirildi",
        ]);
    }

    public function getSurfingId($id): array
    {
        $surfing = SurfingModel::query()->find($id);
        if (!$surfing) {
            return AdminService::returnError('Surfing not found');
        }

        return AdminService::returnSuccess($surfing);
    }

}
