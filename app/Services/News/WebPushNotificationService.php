<?php

namespace App\Services\News;

use App\Http\Requests\SaveNotificationRequest;
use App\Models\NewsModel;
use App\Models\NotificationModel;
use App\ViewModels\JsonReturnViewModel;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

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
class WebPushNotificationService
{

    public function send($request, $news_id): array
    {
        return $this->sendMessage($this->authInfo(), $this->validateMessage($request), $news_id);
    }

    private function sendMessage($auth, $message, $news_id): array
    {
        $webPush       = new WebPush($auth);
        $notifications = [];
        $errors        = [];
        foreach (NotificationModel::query()->get() as $file) {
            $report   = $webPush->sendOneNotification(
                Subscription::create([
                    'endpoint'        => $file->endpoint,
                    'authToken'       => $file->authToken,
                    'contentEncoding' => $file->contentEncoding,
                    'publicKey'       => $file->publicKey,
                ]),
                json_encode($message)
            );
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                $notifications[] = $endpoint;
            } else {
                $errors[] = $endpoint;
                $file->delete();
            }
        }

        $news = NewsModel::query()->find($news_id);
        $news->update([
            'send_count'  => $news->send_count + count($notifications),
            'send_errors' => $news->send_errors + count($errors),
        ]);

        return [
            'notifications' => [
                'count' => count($notifications),
                'data'  => $notifications,
            ],
            'errors'        => [
                'count' => count($errors),
                'data'  => $errors,
            ],
        ];

    }

    private function authInfo(): array
    {
        return [
            'VAPID' => [
                'subject'    => 'https://questa.uz',
                'publicKey'  => 'BM9Ee3p9wSA1hrTqpvB6ADAiFpp8daxDbuVbKV0-4NRpnB4vam-wi3hGtsnMjI7DgqK-Tu4ITKKRVq80_SdHzP0',
                'privateKey' => '2iGLnj-hS0TH228akR-EBqGl3KQU-oUok5V9IYjtSN8',
            ],
        ];
    }

    private function validateMessage($request): array
    {
        $image = $request['image'] ?? route('welcome').asset('notification/news.jpg');
        $title = $request['title'] ?? 'Questa.uz';
        $body  = $request['message'];
        $url   = $request['url'] ?? 'https://questa.uz';

        return [
            'title' => $title,
            'data'  => [
                'body'  => $body,
                'icon'  => route('welcome').asset('notification/favicon.ico'),
                'image' => $image,
                'data'  => [
                    'url' => $url,
                ],
            ],
        ];
    }

    public function pushSubscription(SaveNotificationRequest $request): \Illuminate\Http\JsonResponse|array
    {
        $strJSON   = trim(file_get_contents('php://input'));
        $authToken = $request->input('authToken');
        if (NotificationModel::query()->where('authToken', $authToken)->exists()) {
            return JsonReturnViewModel::toJsonBeautify([
                'status'  => 'error',
                'message' => 'Уведомление с таким авторизационным ключом уже существует',
            ]);
        }

        $notification = NotificationModel::query()->create([
            'endpoint'        => $request->input('endpoint'),
            'publicKey'       => $request->input('publicKey'),
            'authToken'       => $authToken,
            'contentEncoding' => $request->input('contentEncoding'),
            'data'            => $strJSON ?? '',
        ]);

        return JsonReturnViewModel::toJsonBeautify([
            'status'  => 'success',
            'message' => 'subscription saved on server!',
            'data'    => $notification->toArray(),
        ]);

    }


}
