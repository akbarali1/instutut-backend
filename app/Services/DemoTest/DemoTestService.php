<?php

namespace App\Services\DemoTest;

use App\Models\DemoTest;
use App\Models\DemoTestCategoryModel;
use App\Models\Question;
use App\Services\Admin\AdminService;
use App\Services\Helpers\HelpersJson;
use App\ViewModels\Question\QuestionViewModel;

class DemoTestService
{

    public function random($request)
    {
        $DemoTest  = $request['DemoTest'];
        $DemoJavob = $request['DemoJavob'];
        if (DemoTest::query()->whereNull('cat_id')->whereNotIn('id', $DemoTest)->doesntExist()) {
            $jami = array_sum($DemoJavob);
            if ($jami <= 5) {
                $message_text = trans('all.demo_end_message.message_1');
            } elseif ($jami <= 9) {
                $message_text = trans('all.demo_end_message.message_2');
            } elseif ($jami >= 10) {
                $message_text = trans('all.demo_end_message.message_3');
            }
            $data = [
                'complete'        => true,
                'title'           => trans('all.demo_end_message.title'),
                'message_text'    => $message_text,
                'reg_text'        => trans('all.demo_test_end'),
                'answer_response' => $jami.'/11',
                // 'error_answer' => $xato_javob,
            ];

            return HelpersJson::returnSuccess(trans('all.demo_api_message.all_test_check'), $data);
        }
        $item = DemoTest::query()->whereNull('cat_id')->whereNotIn('id', $DemoTest)->inRandomOrder()->first()->toArray();
        shuffle($item['javoblar']);
        $massive = [
            'id'       => $item['id'],
            'question' => $item['savol'],
            'answers'  => $item['javoblar'],
            'money'    => $item['puli'],
            'rating'   => $item['bal'],
            'time'     => $item['berilgan_vaqt'],
        ];

        return $massive;
    }

    public function radomBySlug($slug, $request)
    {
        $DemoTest  = $request['DemoTest'];
        $DemoJavob = $request['DemoJavob'];

        if (!$category = DemoTestCategoryModel::query()->where('slug', $slug)->select(['id'])->first()) {
            return ['message' => 'Category not found', 'code' => 404, 'status' => 'error'];
        }

        if (DemoTest::query()->where('cat_id', $category->id)->whereNotIn('id', $DemoTest)->doesntExist()) {
            $jami = array_sum($DemoJavob);
            if ($jami <= 5) {
                $message_text = trans('all.demo_end_message.message_1');
            } elseif ($jami <= 9) {
                $message_text = trans('all.demo_end_message.message_2');
            } elseif ($jami >= 10) {
                $message_text = trans('all.demo_end_message.message_3');
            }
            $data = [
                'complete'        => true,
                'title'           => trans('all.demo_end_message.title'),
                'message_text'    => $message_text,
                'reg_text'        => trans('all.demo_test_end'),
                'answer_response' => $jami.'/11',
                // 'error_answer' => $xato_javob,
            ];

            return HelpersJson::returnSuccess(trans('all.demo_api_message.all_test_check'), $data);
        }
        $item = DemoTest::query()->where('cat_id', $category->id)->whereNotIn('id', $DemoTest)->inRandomOrder()->first()->toArray();
        shuffle($item['javoblar']);
        $massive = [
            'id'       => (int)$item['id'],
            'question' => $item['savol'],
            'answers'  => $item['javoblar'],
            'money'    => $item['puli'],
            'rating'   => $item['bal'],
            'time'     => $item['berilgan_vaqt'],
        ];

        return $massive;
    }

    public function check($request)
    {
        $demo_model  = DemoTest::query();
        $answer      = $request['Answer'];
        $question_id = $request['QuestionId'];
        $demo_test   = $request['DemoTest'];
        $DemoJavob   = $request['DemoJavob'];

        if ($demo_model->whereNotIn('id', $demo_test)->doesntExist()) {
            $data = ['demo_test' => json_encode($demo_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode($DemoJavob, JSON_FORCE_OBJECT)];

            return HelpersJson::returnError(trans('all.demo_api_message.message_1', $data));
        }

        if (empty($demo_test) || !in_array($question_id, $demo_test)) {
            $return_test = array_merge($demo_test, [$question_id]);
        }

        $demo_test = $demo_model->where('id', $question_id)->first();

        if (!in_array($answer, $demo_test->javoblar)) {
            $data = ['demo_test' => json_encode($demo_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode($DemoJavob, JSON_FORCE_OBJECT)];

            return HelpersJson::returnError(trans('all.demo_api_message.message_4', $data));
        }

        $javob = HelpersJson::returnTrueAnswer($demo_test->javoblar);
        if ($javob !== $answer) {
            $data = ['demo_test' => json_encode($return_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode(array_merge($DemoJavob, [$question_id => '0']), JSON_FORCE_OBJECT)];

            return HelpersJson::returnError(trans('all.demo_api_message.message_2'), $data);
        }

        $data = ['demo_test' => json_encode($return_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode(array_merge($DemoJavob, [$question_id => '1']), JSON_FORCE_OBJECT)];

        return HelpersJson::returnSuccess(trans('all.demo_api_message.message_3'), $data);
    }

    public function checkBySlug($slug, $request)
    {
        if (!$category = DemoTestCategoryModel::query()->where('slug', $slug)->select(['id'])->first()) {
            return AdminService::returnError(['message' => 'Category not found', 'code' => 404, 'status' => 'error']);
        }
        $demo_model  = DemoTest::query()->where('cat_id', $category->id);
        $answer      = $request['Answer'];
        $question_id = $request['QuestionId'];
        $demo_test   = $request['DemoTest'];
        $DemoJavob   = $request['DemoJavob'];

        if ($demo_model->whereNotIn('id', $demo_test)->doesntExist()) {
            $data = ['demo_test' => json_encode($demo_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode($DemoJavob, JSON_FORCE_OBJECT)];

            return HelpersJson::returnError(trans('all.demo_api_message.message_1', $data));
        }

        if (empty($demo_test) || !in_array($question_id, $demo_test)) {
            $return_test = array_merge($demo_test, [$question_id]);
        }

        $demo_test = $demo_model->where('id', $question_id)->first();

        if (!in_array($answer, $demo_test->javoblar)) {
            $data = ['demo_test' => json_encode($demo_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode($DemoJavob, JSON_FORCE_OBJECT)];

            return HelpersJson::returnError(trans('all.demo_api_message.message_4', $data));
        }

        $javob = HelpersJson::returnTrueAnswer($demo_test->javoblar);
        if ($javob !== $answer) {
            $data = ['demo_test' => json_encode($return_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode(array_merge($DemoJavob, [$question_id => '0']), JSON_FORCE_OBJECT)];

            return HelpersJson::returnError(trans('all.demo_api_message.message_2'), $data);
        }

        $data = ['demo_test' => json_encode($return_test, JSON_FORCE_OBJECT), 'demo_javob' => json_encode(array_merge($DemoJavob, [$question_id => '1']), JSON_FORCE_OBJECT)];

        return HelpersJson::returnSuccess(trans('all.demo_api_message.message_3'), $data);
    }

    public function all()
    {
        $questions = DemoTest::query()->orderBy('id', 'desc')->paginate(AdminService::getPaginationLimit());

        return AdminService::returnSuccess($questions);
    }

    public function getQuestionById($id)
    {
        $question = DemoTest::query()->find($id);
        if (!$question) {
            return AdminService::QuestionNotFound();
        }

        return AdminService::returnSuccess($question);
    }

    public function update($request)
    {
        if (DemoTest::query()->where('id', $request->id)->doesntExist()) {
            return AdminService::QuestionNotFound();
        }
        $javoblar = json_decode($request->answers);
        DemoTest::query()->where("id", $request->id)->update(
            [
                'savol'         => trim($request->name),
                'javoblar'      => [
                    trim($javoblar->answer1),
                    trim($javoblar->answer2),
                    trim($javoblar->answer3),
                    trim($javoblar->answer4),
                ],
                'berilgan_vaqt' => trim($request->time),
            ]
        );

        return AdminService::returnSuccess(['message' => 'Malumotlar yangilandi']);
    }

    public function create($request)
    {
        $javoblar = $request['answers'];

        $test = DemoTest::query()->create(
            [
                'savol'         => trim($request['name']),
                'javoblar'      => [
                    trim($javoblar['answer1']),
                    trim($javoblar['answer2']),
                    trim($javoblar['answer3']),
                    trim($javoblar['answer4']),
                ],
                'cat_id'        => trim($request['cat_id']),
                'cat_name'      => trim($request['cat_name']),
                'berilgan_vaqt' => trim($request['time']),
            ]
        );

        return AdminService::returnSuccess(['message' => 'Demo test yaratildi', 'id' => $test->id]);
    }

    public function categoryCreate($request)
    {
        DemoTestCategoryModel::query()->create(
            [
                'name' => trim($request->name),
                'slug' => trim($request->slug),
            ]
        );

        return AdminService::returnSuccess(['message' => 'Kategoriya yaratildi']);
    }
}
