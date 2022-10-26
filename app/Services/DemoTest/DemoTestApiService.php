<?php

namespace App\Services\DemoTest;

use App\Models\DemoTest;
use App\Services\Helpers\HelpersJson;

class DemoTestApiService
{
    public function index($request)
    {
        $demo_model = new DemoTest;
        $answer     = $request['answer'];
        $demoid     = $request['id'];

        if ($demo_model->where('id', $demoid)->doesntExist()) {
            $response = HelpersJson::returnError(trans('all.demo_api_message.message_1'));
        }

        if (empty($_SESSION['demo_test']) || !in_array($demoid, $_SESSION['demo_test'], true)) {
            session()->push('demo_test', $demoid);
        }

        $demo_test = $demo_model->where('id', $demoid)->first();

        if (!in_array($answer, $demo_test->javoblar)) {
            $response = HelpersJson::returnError(trans('all.demo_api_message.message_4'));
        }

        $javob = HelpersJson::returnTrueAnswer($demo_test->javoblar);

        if ($javob != $answer) {
            session()->push('demo_javob', '0');
            $response = HelpersJson::returnError(trans('all.demo_api_message.message_2'));
        } else {
            session()->push('demo_javob', '1');
            $response = HelpersJson::returnSuccess(trans('all.demo_api_message.message_3'));
        }

        return $response;
    }
}
