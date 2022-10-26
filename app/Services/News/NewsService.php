<?php

namespace App\Services\News;

use App\Models\NewsModel;
use App\Services\Admin\AdminService;
use App\Services\News\WebPushNotificationService;
use App\ViewModels\News\NewsViewModel;
use App\ViewModels\News\NewsWebNotificationViewModel;

/**
 * Created by PhpStorm.
 * Filename: NewsService.php
 * Project Name: questa-backend.loc
 * User: Akbarali
 * Date: 26/03/2022
 * Time: 5:22 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */
class NewsService
{

    public function create($request, $user_id, $status = 1,): array
    {
        $photo_name = ($request->hasFile('photo')) ? $this->saveImage($request, AdminService::generateRandomString(40)) : null;

        return AdminService::returnSuccess(
            NewsModel::query()->create([
                'name'        => $request['name'] ?? 'Questa.uz',
                'link'        => $request['link'] ?? null,
                'status'      => $status,
                'description' => $request['description'],
                'content'     => $request['content'],
                'photo'       => $photo_name,
                'user_id'     => $user_id,
            ])
        );
    }

    protected function saveImage($request, $name): string
    {
        if ($files = $request->file('photo')) {
            $destinationPath = public_path('/assets/images/news/');
            $profileImage    = $name.".png";
            $files->move($destinationPath, $profileImage);

            return $profileImage;
        }

        return 'null';
    }

    public function all(): array
    {
        $news = NewsModel::query()->where('status', 1)->orderBy('id', 'desc')->paginate(10);
        $news->transform(function ($value) {
            return new NewsViewModel($value);
        });

        return AdminService::returnSuccess($news);
    }

    public function getNewsById($id): array
    {
        $news = NewsModel::query()->find($id);
        if (!$news) {
            return AdminService::returnError(['message' => 'News not found']);
        }

        return AdminService::returnSuccess(new NewsViewModel($news));
    }

    public function update($request): array
    {
        $id   = $request->id;
        $news = NewsModel::query()->find($id);
        if (!$news) {
            return AdminService::returnError(['message' => 'News not found']);
        }
        $news->name        = $request->name;
        $news->description = $request->description;
        $news->content     = $request->content;
        if ($request->hasFile('photo')) {
            $news->photo = $this->saveImage($request, AdminService::generateRandomString(40));
        }
        $news->save();

        return AdminService::returnSuccess([
            'message' => 'News updated successfully',
            'data'    => new NewsViewModel($news),
        ]);
    }

    public function delete($id): array
    {
        $news = NewsModel::query()->find($id);
        if (!$news) {
            return AdminService::returnError(['message' => 'News not found']);
        }
        $news->delete();

        return AdminService::returnSuccess(['message' => 'News deleted successfully']);
    }

    public function notificationAll(): array
    {
        $news = NewsModel::query()->where('status', 2)->orderBy('id', 'desc')->paginate(10);
        $news->transform(function ($value) {
            return new NewsWebNotificationViewModel($value);
        });

        return AdminService::returnSuccess($news);

    }

    public function notificationSend($id): array
    {
        $news = NewsModel::query()->find($id);
        if (!$news) {
            return AdminService::returnError(['message' => 'News not found']);
        }
        $validate = $this->validate($news->toArray());

        return AdminService::returnSuccess([
            'message'  => 'News notification sent successfully',
            'response' => (new WebPushNotificationService)->send($validate, $id),
        ]);

    }

    public function validate($news): array
    {
        return [
            'title'   => $news['name'],
            'message' => $news['content'],
            'url'     => $news['link'],
            'image'   => $news['photo'],
        ];
    }


}
