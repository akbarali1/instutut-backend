<?php

namespace App\Services\Telegram;

use App\Models\AuthTokenModel;
use App\Models\NotificationModel;
use App\Models\User;
use App\Services\Admin\AdminService;
use App\Services\Championship\ChampionshipService;
use App\Services\Helpers\HelpersJson;
use App\Services\Question\QuestionService;
use App\Services\User\UserService;
use App\ViewModels\JsonReturnViewModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Created by PhpStorm.
 * Filename: ${FILE_NAME}
 * Project Name: questa.loc
 * User: Akbarali
 * Date: 17/01/2022
 * Time: 4:42 PM
 * Github: https://github.com/akbarali1
 * Telegram: @kbarali
 * E-mail: akbarali@webschool.uz
 */
class TelegramService
{
    protected string $bot_token;

    public function __construct()
    {
        $this->bot_token = env('TELEGRAM_API_KEY');
    }

    public function sendTelegram($array, string $sending = 'sendMessage')
    {
        $apiURL = 'https://api.telegram.org/bot'.$this->bot_token.'/';
        $client = new Client(['base_uri' => $apiURL]);

        try {
            $response = $client->post(
                $sending,
                [
                    'query' => $array,
                ]
            );
        } catch (\Exception $ex) {
            return ['ok' => false];
        }

        return json_decode($response->getBody()->getContents(), true);

    }

    public function sendMessage($chat_id, $text, $parse_mode = 'HTML', $disable_notification = false, $reply_to_message_id = null, $reply_markup = null, $protect_content = true)
    {
        return $this->sendTelegram(
            [
                'chat_id'              => $chat_id,
                'text'                 => $text,
                'parse_mode'           => $parse_mode,
                'disable_notification' => $disable_notification,
                'reply_to_message_id'  => $reply_to_message_id,
                'reply_markup'         => ($reply_markup !== null) ? json_encode($reply_markup) : null,
                'protect_content'      => $protect_content,
            ]
        );
    }

    public function pinChatMessage($data, $chat_id, $disable_notification = true)
    {
        $data       = (array)$data;
        $message_id = $data['result']['message_id'];

        return $this->sendTelegram(
            [
                'chat_id'              => $chat_id,
                'message_id'           => $message_id,
                'disable_notification' => $disable_notification,
            ],
            'pinChatMessage'
        );
    }

    public function sendingCheck(int $chat_id): bool
    {
        $res = $this->sendChatAction($chat_id, 'typing');

        return (isset($res['ok']) && $res['ok'] === true);
    }

    public function checkUserTelegram($chat_id, $type)
    {
        if (User::query()->where('telegram_id', $chat_id)->doesntExist()) {
            if ($type !== 'private' && (int)$chat_id !== (int)env('ADMINS_GROUP_ID')) {
                $this->leaveChat($chat_id, $type);
            } else {
                return false;
            }
        } else {
            return User::query()->where('telegram_id', $chat_id)->first();
        }
    }

    public function deleteMessage($chat_id, $message_id)
    {
        return $this->sendTelegram(
            [
                'chat_id'    => $chat_id,
                'message_id' => $message_id,
            ],
            'deleteMessage'
        );
    }

    public function sendChatAction($chat_id, $action)
    {
        return $this->sendTelegram(
            [
                'chat_id' => $chat_id,
                'action'  => $action,
            ],
            'sendChatAction'
        );
    }

    public function leaveChat($chat_id, $chat_type)
    {
        if ($chat_id != env('ADMINS_GROUP_ID')) {
            if ($chat_type != 'private') {
                //            $this->sendMessage($chat_id, '😊');
                //            $this->sendMessage($chat_id, 'Meni bunaqa uyaltirmaylar men faqat shaxsiy chatlarda ishlayman. Omma oldida xayajon bosadi.');
                $this->sendTelegram(
                    [
                        'chat_id' => $chat_id,
                    ],
                    'leaveChat'
                );
                die();
            }
        }
    }

    public function editMessage($chat_id, $message_id, $text, $parse_mode = 'HTML', $disable_web_page_preview = true, $disable_notification = false, $reply_to_message_id = null, $reply_markup = null)
    {
        return $this->sendTelegram(
            [
                'chat_id'                  => $chat_id,
                'message_id'               => $message_id,
                'text'                     => $text,
                'parse_mode'               => $parse_mode,
                'disable_web_page_preview' => $disable_web_page_preview,
                'disable_notification'     => $disable_notification,
                'reply_to_message_id'      => $reply_to_message_id,
                'reply_markup'             => $this->keyboardNext(),
            ],
            'editMessageText'
        );
    }

    public function input($request)
    {
        $chat_id    = $request['callback_query']['message']['chat']['id'] ?? ((!empty($request['message']['chat']['id'])) ? $request['message']['chat']['id'] : null);
        $message_id = isset($request['callback_query']['message']['chat']['id']) && !empty($request['callback_query']['message']['chat']['id']) ? $request['callback_query']['message']['message_id'] : ((!empty($request['message']['message_id'])) ? $request['message']['message_id'] : null);
        $chat_type  = isset($request['callback_query']['message']['chat']['type']) && !empty($request['callback_query']['message']['chat']['type']) ? $request['callback_query']['message']['chat']['type'] : ((!empty($request['message']['chat']['type'])) ? $request['message']['chat']['type'] : null);

        $text = (!empty($request['message']['text'])) ? $request['message']['text'] : '';
        $user = $this->checkUserTelegram($chat_id, $chat_type);
        if ($user === false) {
            $text_user = "\n<b>Siz akkauntingizni bog'lamagan ko'rinasiz yoki ro'yhatdan o'tmagansiz!</b>";
            $text_user .= "\n<b>Iltimos Akkauntingizni ulang yoki ro'yhatdan o'ting!</b>";

            $inline_keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Telegramni ulash', 'web_app' => ['url' => route('telegram.question')]],
                    ],
                    [
                        ['text' => "Ro'yhatdan o'tish", 'url' => 'https://questa.uz/auth/register'],
                    ],
                ],
            ];

            $this->sendMessage($chat_id, $text_user, 'HTML', 'false', null, $inline_keyboard);

            return JsonReturnViewModel::toJsonBeautify([
                'success' => true,
            ]);

        }
        if ($chat_type !== 'private') {
            return AdminService::emptyResponse();
        }
        if (!isset($chat_id)) {
            return AdminService::returnSuccess();
        }
        $this->sendChatAction($chat_id, 'typing');

        if (!empty($request['callback_query'])) {
            return $this->callbackControl($request, $chat_id, $message_id, $chat_type, $user, $text);
        }

        //        if (!empty($request)) {
        //
        //            $message = $this->sendTelegram(
        //                [
        //                    'chat_id'                  => $chat_id,
        //                    'text'                     => '🗑️ ',
        //                    'parse_mode'               => 'HTML',
        //                    'message_auto_delete_time' => 10,
        //                    'protect_content'          => true,
        //                ]
        //            );
        //            Log::info($request);
        //            Log::info($message);
        //            die();
        //        }

        if ($text === '/start') {
            $first_name = (!empty($request['message']['from']['first_name'])) ? $request['message']['from']['first_name'] : '';
            $user_star  = QuestionService::getUserStar($user->id, $user->status_id);
            $text_user  = "Salom ".$first_name;
            //            if ($user->id == 15) {
            //                $text_user .= "\n\n<b>Siz demo profildan foydalanyapsiz</b>";
            //                $text_user .= "\n<b>Real balansda ishlash uchun <a href='https://questa.uz/auth/register'>Ro'yhatdan o'ting</a></b>";
            //            }
            $text_user .= "\n\nUsername: <b>".$user->name."</b>";
            $text_user .= "\n<b>Hozirgi balansingiz haqida ma'lumot</b>";
            $text_user .= "\n⭐️ <b>".$user_star.'</b> yulduzcha';
            $text_user .= "\n💰 <b> ".HelpersJson::NumberFormat($user->money)."</b> crypto";
            $text_user .= "\n🌟 <b> ".HelpersJson::NumberFormat($user->rating)."</b> reyting";
            //            $text_user .= "\n\n<b>Testni boshlash uchun /test_start buygug'ini jo'nating</b>";
            //            if ($user->id == 15) {
            //                $text_user .= "\n<b>Ma'lumotlarni tozalash uchun /reset buygug'ini jo'nating</b>";
            //            }

            $inline_keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Testni boshlash', 'web_app' => ['url' => route('telegram.question')]],
                    ],
                ],
            ];

            return $this->sendMessage($chat_id, $text_user, 'HTML', 'false', null, $inline_keyboard);
        }

        //        if ($text == '/test_start') {
        //            $this->sendPoll($chat_id, $chat_type, $text, $user);
        //            $this->deleteMessage($chat_id, $message_id);
        //
        //            return AdminService::returnSuccess();
        //        }

        if ($text === '/me') {
            return $this->sendMessage($chat_id, 'https://questa.uz/users/'.$user->id);
        }

        if ($text === '/reset') {
            (new UserService())->userRestore(15);

            return $this->sendMessage($chat_id, "Hamma ma'lumotlar tozalandi. \n /start komandasini jo`nating");
        }

        if ($text === '/id') {
            (new UserService())->userRestore(15);

            return $this->sendMessage($chat_id, $chat_id);
        }

        if ($text === '/alltelegram') {
            $this->allTelegramAccounts($chat_id);

            return AdminService::returnSuccess();
        }

        if ($text === '/allphone') {
            return $this->allPhone($chat_id);
        }

        if (!empty($text)) {
            return $this->sendMessage($chat_id, 'Bunday komand topilmadi');
        }

        return AdminService::returnSuccess();
    }

    public function allPhone($chat_id)
    {
        //        $this->sendMessage($chat_id, 'Bunday komand topilmadi');
        if ($chat_id === 414229140) {
            $count     = NotificationModel::query()->count();
            $text_user = "Jamil: <b>".$count."</b>";

            return $this->sendMessage($chat_id, $text_user, 'HTML', 'false', null, null, false);
        }

        return $this->sendMessage($chat_id, 'Bunday komand topilmadi');
    }

    public function allTelegramAccounts($chat_id)
    {
        //        $this->sendMessage($chat_id, 'Bunday komand topilmadi');
        if ($chat_id === 414229140) {
            $users = User::query()->whereNotNull('telegram_id')
                ->where('rights', '=', 1)
                ->select(['telegram_id', 'name', 'username'])->orderBy('telegram_id')->get();
            $text  = '';
            $i     = 0;
            foreach ($users as $item) {
                $i++;

                if ($item['username']) {
                    $text .= $i.') <a href="https://t.me/'.$item['username'].'">'.$item['name'].' | '.$item['username'].' </a>'."\n";
                } else {
                    $text .= $i.') <a href="tg://user?id='.$item['telegram_id'].'">'.$item['name'].' | '.$item['telegram_id'].' </a>'."\n";
                }
                //            $keyboard[] = [
                //                [
                //                    'text' => $item->name,
                //                    'url'  => 'tg://user?id='.$item->telegram_id,
                //                ],
                //            ];
            }
            //        $keyboard = ['inline_keyboard' => $keyboard];
            $this->sendMessage($chat_id, $text, 'HTML', 'false', null, null, false);
        } else {
            $this->sendMessage($chat_id, 'Bunday komand topilmadi');
        }
    }

    public function callbackControl($request, $chat_id, $message_id)
    {
        try {
            $data = json_decode($request['callback_query']['data'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return AdminService::returnSuccess();
        }

        if ($auth = AuthTokenModel::query()->find($data['authId'])) {
            if ($auth->block === 1) {
                return $this->sendMessage($chat_id, '<b>Avvaldan kirish bloklangan</b>', reply_to_message_id: $message_id);
            }

            JWTAuth::setToken($auth->token)->invalidate();
            $auth->increment('block');

            $res = $this->sendMessage($chat_id, '<b>Kirish bloklandi</b>', reply_to_message_id: $message_id);

            return $this->pinChatMessage($chat_id, $res);
        }

        return AdminService::emptyResponse();

    }

    public function callbackNext($chat_id, $message_id, $chat_type, $text, $user)
    {
        $this->sendPoll($chat_id, $chat_type, $text, $user);
        $this->deleteMessage($chat_id, $message_id);

        return AdminService::returnSuccess();
    }

    public function callbackMessage($data, $chat_id, $message_id, $user)
    {
        $test_id          = $data['question_id'];
        $answer           = $data['answer'];
        $response_service = QuestionService::testCheck($test_id, $answer, $user->id, $user->status_id, $user->money, $user->rating);
        $user_star        = QuestionService::getUserStar($user->id, $user->status_id);

        $user_ball   = (isset($response_service['new_money']) && !empty($response_service['new_money'])) ? $response_service['new_money'] : HelpersJson::NumberFormat($user->money);
        $user_rating = (isset($response_service['new_rating']) && !empty($response_service['new_rating'])) ? $response_service['new_rating'] : HelpersJson::NumberFormat($user->rating);
        $user_icon   = (isset($response_service['success']) && $response_service['success'] == true) ? '✅' : '❌';

        $plus_star       = $user_star + 1;
        $user_minus_star = (isset($response_service['minus_star']) && !empty($response_service['minus_star'])) ? $plus_star.' - '.'1' : $user_star;

        $return_success_message = $user_icon.' ';
        $return_success_message .= '<b>'.$response_service['message'].'</b>';
        $return_success_message .= "\n\n<b>Hozirgi balansingiz haqida ma'lumot</b>";
        $return_success_message .= "\n⭐️ <b>".$user_minus_star.'</b> yulduzcha';
        $return_success_message .= "\n💰 <b>".$user_ball."</b> crypto";
        $return_success_message .= "\n🌟 <b>".$user_rating."</b> reyting";

        $this->deleteMessage($chat_id, $message_id);

        return $this->sendMessage($chat_id, $return_success_message, "HTML", true, false, null, $this->keyboardNext());
    }

    public function keyboardNext()
    {
        return json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text'          => '❌',
                        'callback_data' => 'exit',
                    ],
                    [
                        'text'          => '➡️',
                        'callback_data' => 'next',
                    ],
                ],
            ],
        ]);
    }

    public function sendPoll($chat_id, $type, $text, $user)
    {
        $return_quiz = $this->quizGenerate($user->id, $user->status_id, $user->money, $user->rating, $chat_id, $user->rights);

        if (isset($return_quiz['photo']) && !empty($return_quiz['photo'])) {
            $this->sendTelegram(
                [
                    'chat_id'         => $chat_id,
                    'photo'           => $return_quiz['file'],
                    'caption'         => $return_quiz['question'],
                    'protect_content' => true,
                    'reply_markup'    => $return_quiz['keyboard'],
                    'parse_mode'      => 'HTML',
                ],
                'sendPhoto'
            );
        } else {
            $this->sendTelegram(
                [
                    'chat_id'         => $chat_id,
                    'text'            => $return_quiz['question'],
                    'protect_content' => true,
                    'reply_markup'    => $return_quiz['keyboard'],
                    'parse_mode'      => 'HTML',
                ]
            );
        }

        return AdminService::returnSuccess();

    }

    public function quizGenerate($user_id, $status_id, $money, $rating, $chat_id, $rights)
    {
        $user_star = QuestionService::getUserStar($user_id, $status_id);
        if ($user_star <= 0) {
            $text_error_message = "\n\nSotib olish: https://questa.uz/exchange";
            $text_error_message .= "\n\n".trans('all.surfing_star_add');

            $this->sendMessage($chat_id, "<b>".trans('all.test.all_errors_end').$text_error_message."</b>");
            die();
        }

        $question    = QuestionService::getQuestion($user_id, $status_id);
        $javoblar    = $question->answer;
        $true_answer = $question->answer[0];
        unset($question->answer);
        shuffle($javoblar);

        foreach ($javoblar as $item) {
            $keyboard[] = [
                [
                    'text'          => '🔘 '.$item,
                    'callback_data' => json_encode(
                        [
                            'answer'      => $item,
                            'question_id' => $question->id,

                        ]
                    ),
                ],
            ];
        }

        $keyboard[] = [
            [
                'text'          => "❌",
                'callback_data' => 'exit',
            ],
            [
                'text'          => "➡️",
                'callback_data' => 'next',
            ],
        ];

        if ($rights > 1) {
            $keyboard[] = [
                [
                    'text' => "✏️",
                    'url'  => 'https://questa.uz/admin/question/edit/'.$question->id,

                ],
                [
                    'text' => "👀",
                    'url'  => 'https://questa.uz/admin/question/view/'.$question->id,
                ],
            ];
        }

        $keyboard = ['inline_keyboard' => $keyboard];

        $text = '❓ <b>'.$question->question."</b>\n\n";
        if ($rights > 1) {
            $text .= "✅ <b>".$true_answer."</b>\n";
        }
        //            $text .= '⏰ <b>' . $question->time . '</b> soniya';
        $text .= "⭐️ <b>".$user_star.'</b> yulduzcha';
        $text .= "\n💰 <b> ".HelpersJson::NumberFormat($money)." + ".HelpersJson::NumberFormat($question->money)."</b> crypto";
        $text .= "\n🌟 <b> ".HelpersJson::NumberFormat($rating)." + ".HelpersJson::NumberFormat($question->rating)."</b> reyting";

        return [
            'question' => $text,
            'file'     => $question->file,
            'keyboard' => json_encode($keyboard),
        ];

    }

    public static function AdminsNotification($text): void
    {
        (new self())->sendMessage(env('ADMINS_GROUP_ID'), $text);
    }

    public function getUsersInfo($request)
    {
        $telegram_id       = $request->input('telegram_id');
        $data_check_string = $request->input('data_check_string');

        $data_check_arr = explode('&', rawurldecode($data_check_string));
        $needle         = 'hash=';
        $check_hash     = false;
        foreach ($data_check_arr as &$val) {
            if (substr($val, 0, strlen($needle)) === $needle) {
                $check_hash = substr_replace($val, '', 0, strlen($needle));
                $val        = null;
            }
        }
        $data_check_arr = array_filter($data_check_arr);
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key        = hash_hmac('sha256', env('TELEGRAM_API_KEY'), "WebAppData", true);
        $hash              = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

        if ($request->has('save_telegram') && $request->input('save_telegram') === true) {
            return true;
        }
        if ((strcmp($hash, $check_hash) === 0) && User::query()->where('telegram_id', $telegram_id)->exists()) {
            $user = User::query()->where('telegram_id', $telegram_id)->first();
            if (!$user->username) {
                $user->username = $request->input('username');
                $user->save();
            } elseif ($user->username !== $request->input('username')) {
                $user->username = $request->input('username');
                $user->save();
            }
            Auth::login($user);

            return $user;
        }

        return false;
    }

    public function getUserProfilePhotos($user_id)
    {
        $data   = $this->sendTelegram(
            [
                'user_id' => $user_id,
            ],
            'getUserProfilePhotos'
        );
        $photos = json_decode(json_encode($data), true);
        if (isset($photos['result']['photos'][0][0]['file_id'])) {
            return $this->getFile($photos['result']['photos'][0][0]['file_id']);
        }

        return null;
    }

    public function getFile($file_id)
    {
        $data   = $this->sendTelegram(
            [
                'file_id' => $file_id,
            ],
            'getFile'
        );
        $photos = json_decode(json_encode($data), true);
        if (isset($photos['result']['file_path'])) {
            return $photos['result']['file_path'];
        }

        return null;
    }

    public static function checkTelegramAuthorization($auth_data): bool
    {
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key.'='.$value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key        = hash('sha256', (new TelegramService)->getBotToken(), true);
        $hash              = hash_hmac('sha256', $data_check_string, $secret_key);

        //        if (strcmp($hash, $check_hash) !== 0) {
        //            return false;
        //        }
        //        if ((time() - $auth_data['auth_date']) > 86400) {
        //            return false;
        //        }
        //        return true;

        return !(strcmp($hash, $check_hash) !== 0 && (time() - $auth_data['auth_date']) > 86400);

    }

    public function getBotToken()
    {
        return $this->bot_token;
    }

    public function connectedCongratulations($telegram_id)
    {
        return $this->sendMessage($telegram_id, "Akkauntingiz muvafaqiyatli ulandi. \n\nTabriklaymiz! 🎉🎉🎉");
    }

}
