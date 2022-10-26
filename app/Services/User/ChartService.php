<?php

namespace App\Services\User;

use App\Filters\Question\DateBetweenFilter;
use App\Models\ChampionshipSessions;
use App\Models\ChampionshipSessionsModel;
use App\Models\SurfingSessionModel;
use App\Models\TestSessions;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use Carbon\Carbon;
use Illuminate\Routing\Pipeline;

/**
 * Created by PhpStorm.
 * Filename: ChartService.php
 * Project Name: questa-backend.loc
 * Author: Акбарали
 * Date: 07/08/2022
 * Time: 1:24 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class ChartService
{

    public function getChartUserTests($user_id, $request): array
    {
        if ((new ChampionshipService())->active()) {
            $query = ChampionshipSessions::query();
        } else {
            $query = TestSessions::query();
        }
        $start_time = $request['start_date'] ?? AdminService::getCarbonSubdaysDefault();
        $end_time   = $request['end_date'] ?? AdminService::getDate();
        $start_time = Carbon::parse($start_time)->startOfDay()->toDateTimeString();
        $end_time   = Carbon::parse($end_time)->endOfDay()->toDateTimeString();
        //dd([$start_time, $end_time], $query->where('user_id', $user_id)->get()->toArray());
        $natija      = $query->where('user_id', $user_id)
            ->whereBetween('created_at', [$start_time, $end_time])
            ->orderBy('created_at')
            ->select(['id', 'status', 'created_at', 'user_id'])->get();
        $collect     = collect($natija);
        $groupedData = $collect->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('d.m.Y');
        });

        return [
            'colors' => ['#00ff00', '#ff0000', '#ebe834'],
            'area'   => [
                'categories'           => $groupedData->keys(),
                'true_answer'          => $groupedData->map(function ($item) {
                    return $item->where('status', 1)->count();
                })->values(),
                'false_answer'         => $groupedData->map(function ($item) {
                    return $item->where('status', 0)->count();
                })->values(),
                'premium_false_answer' => $groupedData->map(function ($item) {
                    return $item->where('status', 2)->count();
                })->values(),
            ],
            'pie'    => [
                'series' => [$collect->where('status', 1)->count(), $collect->where('status', 0)->count(), $collect->where('status', 2)->count()],

            ],
        ];
    }

    public function getSurfungStat(): array
    {
        $query       = app(Pipeline::class)->send(SurfingSessionModel::query())->through([
            DateBetweenFilter::class,
        ])->thenReturn()->select(['id', 'created_at', 'user_id'])->get()
            ->transform(function ($item) {
                return [
                    'id'         => $item->id,
                    'user_id'    => $item->user_id,
                    'created_at' => Carbon::parse($item->created_at)->format('d.m.Y'),
                ];
            });
        $collect     = collect($query);
        $groupedData = $collect->groupBy('created_at');
        $dates       = $groupedData->keys();

        $users = $collect->groupBy('user_id')->transform(function ($item) use ($dates) {
            $res = [];
            foreach ($dates as $date) {
                $res[] = $item->where("created_at", $date)->count();
            }

            return [
                "name" => $item->first()["user_id"],
                "data" => $res,
            ];
        })->sortBy('name');

        return [
            'area'  => [
                'categories' => $dates->values(),
                'stat'       => $groupedData->map(function ($item) {
                    return $item->count();
                })->values(),
            ],
            'users' => [
                'categories' => $dates->values(),
                'stat'       => $users->values(),
            ],
        ];
    }


}
