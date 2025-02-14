<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyAnswerRequest;
use App\Http\Resources\SurveyAnswerResource;
use App\Models\QuestionAnswer;
use App\Models\SurveyAnswer;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class SurveyAnswerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');

        $SurveyAnswer = SurveyAnswer::
            when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            SurveyAnswerResource::collection($SurveyAnswer);
        } else {
            $SurveyAnswer = SurveyAnswerResource::collection($SurveyAnswer);
        }
        return $this->responseSuccess('Survey answers display successfully', $SurveyAnswer);
    }

    public function store(SurveyAnswerRequest $request){

        $create_survey_answer = SurveyAnswer::create([
            "target_location_id" => $request["target_location_id"],
            "name" => $request["name"],
            "age" => $request["age"],
            "gender" => $request["gender"],
            "address" => $request["address"],
            "contact_number" => $request["contact_number"],
            "date" =>  $request["date"],
            "questionnaire_answer" => $request["questionnaire_answer"],
            "surveyor_id" => $request["surveyor_id"],
            "submit_date" => date('Y-m-d H:i:s')
        ]);

        $questionnaire_answers = $request->input('questionnaire_answer');

        // Loop through each section
        foreach ($questionnaire_answers as $questionnaire_answer) {
            // Loop through each question within the section
            foreach ($questionnaire_answer['questions'] as $question) {
                // Handle grid type questions separately
                if ($question['questionType'] === 'grid') {
                    foreach ($question['answer'] as $gridAnswer) {
                        if ($gridAnswer['rowAnswer'] === "") {
                            continue; // Skip if the answer is empty
                        }

                        $finalAnswer = $gridAnswer['rowAnswer'] === "Other"
                            ? (is_array($gridAnswer['otherAnswer']) ? implode(', ', $gridAnswer['otherAnswer']) : $gridAnswer['otherAnswer'])
                            : $gridAnswer['rowAnswer'];

                        QuestionAnswer::create([
                            'survey_id' => $create_survey_answer->id,
                            'question_type' => $question['questionType'],
                            'question' => $question['questionName'] . ' - ' . $gridAnswer['rowQuestion'],
                            'answer' => $finalAnswer,
                        ]);
                    }
                } else {
                    $answers = is_array($question['answer']) ? $question['answer'] : [$question['answer']];

                    foreach ($answers as $answer) {
                        if ($answer === "") {
                            continue; // Skip if the answer is empty
                        }

                        $finalAnswer = $answer === "Other"
                            ? (is_array($question['otherAnswer']) ? implode(', ', $question['otherAnswer']) : $question['otherAnswer'])
                            : $answer;

                        QuestionAnswer::create([
                            'survey_id' => $create_survey_answer->id,
                            'question_type' => $question['questionType'],
                            'question' => $question['questionName'],
                            'answer' => $finalAnswer,
                        ]);
                    }
                }
            }
        }
        return $this->responseCreated('Survey Answer Successfully Sync', $create_survey_answer);
    }

}
