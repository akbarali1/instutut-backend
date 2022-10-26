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

namespace App\Services\Rating;

use App\Filters\Question\ReferalSortFilter;
use App\Models\ChampionshipModel;
use App\Models\OldChampionshipModel;
use App\Models\User;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use App\ViewModels\Rating\RatingChampionshipViewModel;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\DB;

class RatingService
{
    /**
     * @var ChampionshipService
     */
    protected ChampionshipService $championshipService;

    public function __construct(ChampionshipService $championshipService)
    {
        $this->championshipService = $championshipService;
    }

    public function getUsersRating(): array
    {
        $rating = User::query()
            ->where('rights', '=', 1)
            ->where('ban', '!=', 1)
            ->where('rights', '!=', 0)
            ->orderBy('rating', 'desc')
            ->select(['id', 'name', 'rating', 'status_name', 'status_id', 'question', 'telegram_id'])
            ->paginate(100);

        $data = [];
        foreach ($rating->items() as $key => $item) {
            $number = $rating->firstItem() + $key;
            $data[] = [
                'number'      => $number,
                'id'          => $item->id,
                'name'        => $item->name,
                'rating'      => $item->rating,
                'status_name' => $item->status_name,
                'status_id'   => $item->status_id,
                'question'    => $item->question,
                'telegram_id' => $item->telegram_id,
            ];

        }
        $rating         = $rating->toArray();
        $rating['data'] = $data;

        return AdminService::returnSuccess($rating);
    }

    public function getReferalRating(): array
    {
        $query  = app(Pipeline::class)->send(
            User::query()
                ->where('users.ban', '!=', 1)
                ->where('users.rights', '=', 1)
                ->leftJoin('users as referal', 'users.id', '=', 'referal.ref_id')
                ->whereNotNull('referal.id')
                ->select([
                    'users.id',
                    'users.name',
                    'users.telegram_id',
                    DB::raw('count(referal.id) as total_referal'),
                    DB::raw("(SELECT COUNT(ac_r.id) FROM users as ac_r WHERE ac_r.ref_id=users.id AND ac_r.ref_bonus = 1) as active_referal"),
                    DB::raw("(SELECT COUNT(ac_r.id) FROM users as ac_r WHERE ac_r.ref_id=users.id AND ac_r.ref_bonus != 1) as no_active_referal"),
                    DB::raw("((SELECT COUNT(ac_r.id) FROM users as ac_r WHERE ac_r.ref_id=users.id AND ac_r.ref_bonus = 1) * ".User::REFERAL_BONUS.") as total_money_referal"),
                ])
                ->groupBy('users.id')
        )->through([
            ReferalSortFilter::class,
        ])->thenReturn();
        $rating = $query->orderBy('total_referal')->paginate(100);
        $data   = [];
        foreach ($rating->items() as $key => $item) {
            $number = $rating->firstItem() + $key;
            $data[] = [
                'number'              => $number,
                'id'                  => $item->id,
                'name'                => $item->name,
                'telegram_id'         => $item->telegram_id,
                'total_referal'       => $item->total_referal,
                'active_referal'      => $item->active_referal,
                'total_money_referal' => $item->total_money_referal,
                'no_active_referal'   => $item->no_active_referal,
            ];
        }
        //        dd($data);
        $rating         = $rating->toArray();
        $rating['data'] = $data;

        return AdminService::returnSuccess($rating);
    }

    public function getChampionshipRating($id): array
    {
        $activeChampionship = ($id === false) ? $this->championshipService->getActiveChampionshipEndTime() : $this->championshipService->findChampionshipById($id);
        if (isset($activeChampionship['error'])) {
            return $activeChampionship;
        }

        $rating = User::query()
            ->where('rights', '=', 1)
            ->where('ban', '!=', 1)
            ->where('rights', '!=', 0)
            ->join('championship_sessions', 'users.id', '=', 'championship_sessions.user_id')
            ->whereBetween('championship_sessions.created_at', [
                $activeChampionship['data']['start_time'],
                $activeChampionship['data']['end_time'],
            ])
            ->where('championship_sessions.status', 1)
            ->groupBy('users.id')
            ->select([
                'users.id',
                'users.name',
                'users.telegram_id',
                DB::raw('SUM(championship_sessions.rating) as rating'),
                DB::raw('SUM(championship_sessions.status) as question'),
                'users.status_name',
                'users.status_id',
            ])->orderBy('rating', 'desc')
            ->paginate(100);

        $awards = DB::table('championship_awards')->where('chem_id', '=', $activeChampionship['data']['id'])
            ->select('name')->pluck('name')->toArray();
        $data   = [];
        foreach ($rating->items() as $key => $item) {
            $number   = $rating->firstItem() + $key;
            $is_given = $this->givenAward($activeChampionship, $item->rating, $number);
            $data[]   = [
                'id'          => $item->id,
                'number'      => $number,
                'award'       => $this->getAward($is_given, $key, $awards),
                'is_given'    => $is_given,
                'name'        => $item->name,
                'telegram_id' => $item->telegram_id,
                'rating'      => $item->rating,
                'status_id'   => $item->status_id,
                'status_name' => $item->status_name,
                'question'    => $item->question,
            ];
        }
        $count_price = collect($data)->where('is_given', '=', true)->pluck('id')->count();
        $new_data    = [];
        foreach ($data as $key => $item) {
            $res          = $item;
            $res['award'] = $this->getAward($res['is_given'], $key, array_slice($awards, -$count_price, $count_price));
            $new_data[]   = new RatingChampionshipViewModel($res);
        }

        $rating                 = $rating->toArray();
        $rating['championship'] = $activeChampionship['data'];
        $rating['data']         = $new_data;

        return AdminService::returnSuccess($rating);
    }

    public function getChampionshipRatingOld($id): array
    {
        $activeChampionship = ($id === false) ? $this->championshipService->getActiveChampionshipEndTime() : $this->championshipService->findChampionshipById($id);
        if (isset($activeChampionship['error'])) {
            return $activeChampionship;
        }

        $data = OldChampionshipModel::query()
            ->where('championship_id', '=', $id)
            ->join('users', 'users.id', '=', 'old_championship.user_id')
            ->select([
                'users.id',
                'users.name',
                'users.status_name',
                'users.status_id',
                'users.telegram_id',
                'old_championship.number',
                'old_championship.award',
                'old_championship.is_given',
                'old_championship.rating',
                'old_championship.question',
            ])
            ->orderBy('rating', 'desc')
            ->paginate(100);

        $new_data = [];
        foreach ($data as $item) {
            $new_data[] = new RatingChampionshipViewModel($item);
        }
        $rating                 = $data->toArray();
        $rating['championship'] = $activeChampionship['data'];
        $rating['data']         = $new_data;

        return AdminService::returnSuccess($rating);
    }

    public function getChampionshipRatingByIdInfo($id): array
    {
        return AdminService::returnSuccess(ChampionshipModel::query()->select(['id', 'name', 'content', 'start_time', 'end_time'])->findOrFail($id));
    }

    //    public function getChampionshipRatingById($id): array
    //    {
    //        $activeChampionship = $this->championshipService->findChampionshipById($id);
    //
    //        $rating = User::query()
    //            ->where('rights', 1)
    //            ->where('rights', '!=', '0')
    //            ->join('championship_sessions', 'users.id', '=', 'championship_sessions.user_id')
    //            ->whereBetween('championship_sessions.created_at', [
    //                $activeChampionship['data']['start_time'],
    //                $activeChampionship['data']['end_time'],
    //            ])
    //            ->where('championship_sessions.status', '=', 1)
    //            ->groupBy('users.id')
    //            ->select([
    //                'users.id',
    //                'users.name',
    //                'users.telegram_id',
    //                DB::raw('SUM(championship_sessions.rating) as rating'),
    //                DB::raw('SUM(championship_sessions.status) as question'),
    //                'users.status_name',
    //                'users.status_id',
    //            ])
    //            ->orderBy('rating', 'desc')
    //            ->paginate(100);
    //
    //        $awards = DB::table('championship_awards')->where('chem_id', '=', $activeChampionship['data']['id'])
    //            ->select('name')->pluck('name')->toArray();
    //        $data   = [];
    //        foreach ($rating->items() as $key => $item) {
    //            $number   = $rating->firstItem() + $key;
    //            $is_given = $this->givenAward($activeChampionship, $item->rating, $number);
    //            $res      = [
    //                'id'          => $item->id,
    //                'number'      => $number,
    //                'award'       => $this->getAward($is_given, $key, $awards),
    //                'is_given'    => $is_given,
    //                'name'        => $item->name,
    //                'telegram_id' => $item->telegram_id,
    //                'rating'      => $item->rating,
    //                'status_id'   => $item->status_id,
    //                'status_name' => $item->status_name,
    //                'question'    => $item->question,
    //            ];
    //            $data[]   = new RatingChampionshipViewModel($res);
    //        }
    //        $rating                 = $rating->toArray();
    //        $rating['championship'] = $activeChampionship['data'];
    //        $rating['data']         = $data;
    //
    //        return AdminService::returnSuccess($rating);
    //    }

    public function getChampionshipAll(): array
    {
        $championship = ChampionshipModel::query()
            ->orderBy('id', 'desc')
            ->paginate(AdminService::getPaginationLimit());

        return AdminService::returnSuccess($championship);
    }

    private function givenAward($activeChampionship, $rating, $number): bool
    {
        return ($activeChampionship['data']['ball'] <= $rating && $number <= $activeChampionship['data']['count']);
    }

    private function getAward($given, $key, $array): string
    {
        return ($given) ? $array[$key] ?? 'Berilmaydi' : 'Berilmaydi';
    }

}
