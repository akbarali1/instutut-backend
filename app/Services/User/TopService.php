<?php

namespace App\Services\User;

use App\Models\ChampionshipModel;
use App\Models\ChampionshipRatingModel;
use App\Models\User;
use App\Services\Admin\AdminService;
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
 * Filename: TopService.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 19/08/2022
 * Time: 10:26 AM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class TopService
{
    public function getNine(): array
    {
        $championship = $this->getChampionshipRating();
        $user         = $this->getUserRating();

        return [
            'championship' => $championship,
            'user'         => $user,
        ];
    }

    public function insertChampionshipRating()
    {
        $history      = ChampionshipModel::query()->orderByDesc('id')->select(['id', 'start_time', 'end_time'])->first();
        $i            = 0;
        $championship = User::query()
            ->where('rights', 1)
            ->join('championship_sessions', 'users.id', '=', 'championship_sessions.user_id')
            ->whereBetween('championship_sessions.created_at', [
                $history->start_time,
                $history->end_time,
            ])
            ->where('championship_sessions.status', 1)
            ->groupBy('users.id')
            ->select(['users.id', 'users.name', 'users.telegram_id', DB::raw('SUM(championship_sessions.rating) as rating'), 'users.status_name', 'users.status_id'])
            ->orderBy('rating', 'desc')
            ->limit(AdminService::getRatingLimit())
            ->get()->transform(function ($item) use (&$i, $history) {
                return [
                    'championship_id' => $history->id,
                    'number'          => ++$i,
                    'rating'          => $item->rating,
                    'user_id'         => $item->id,
                    'created_at'      => date('Y-m-d H:i:s'),
                ];
            })->toArray();

        return ChampionshipRatingModel::query()->insert($championship);
        //        return 5;
    }

    public function getChampionshipRating()
    {
        return ChampionshipRatingModel::query()
            ->join('users', 'championship_rating_10.user_id', '=', 'users.id')
            ->select([
                'championship_rating_10.*',
                'users.name',
                'users.photo',
            ])
            ->orderByDesc('championship_id')
            ->orderByDesc('rating')
            ->limit(AdminService::getRatingLimit())->get()->transform(function ($item) {
                $item->photo = HelpersJson::getPhoto($item->photo);

                return $item;
            });
    }

    public function getUserRating()
    {
        $i = 0;

        return User::query()->orderByDesc('rating')->select(['id', 'name', 'rating', 'photo'])
            ->limit(AdminService::getRatingLimit())->get()
            ->transform(function ($item) use (&$i) {
                $item->photo  = HelpersJson::getPhoto($item->photo);
                $item->number = ++$i;

                return $item;
            });
    }
}
