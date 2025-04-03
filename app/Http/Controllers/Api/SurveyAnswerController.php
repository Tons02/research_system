<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyAnswerRequest;
use App\Http\Resources\SurveyAnswerResource;
use App\Models\QuestionAnswer;
use App\Models\SurveyAnswer;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        DB::beginTransaction(); // Start the transaction

        try {
            $create_survey_answer = SurveyAnswer::create([
                "target_location_id" => $request["target_location_id"],
                "name" => $request["name"],
                "age" => $request["age"],
                "gender" => $request["gender"],
                "address" => $request["address"],
                "contact_number" => $request["contact_number"],
                "date" =>  $request["date"],
                "family_size" =>  $request["family_size"],
                "income_class" =>  $request["income_class"],
                "sub_income_class" =>  $request["sub_income_class"],
                "monthly_utility_expenses" =>  $request["monthly_utility_expenses"],
                "sub_monthly_utility_expenses" =>  $request["monthly_utility_expenses"],
                "educational_attainment" =>  $request["educational_attainment"],
                "employment_status" =>  $request["employment_status"],
                "occupation" =>  $request["occupation"],
                "structure_of_house" =>  $request["structure_of_house"],
                "ownership_of_house" =>  $request["ownership_of_house"],
                "questionnaire_answer" => $request["questionnaire_answer"],
                "surveyor_id" => auth('sanctum')->user()->id,
            ]);

            $questionnaire_answers = $request->input('questionnaire_answer');

            foreach ($questionnaire_answers as $questionnaire_answer) {
                foreach ($questionnaire_answer['questions'] as $question) {
                    if ($question['questionType'] === 'grid') {
                        foreach ($question['answer'] as $gridAnswer) {
                            if ($gridAnswer['rowAnswer'] === "") {
                                continue;
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
                                continue;
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
            throw new \Exception('Forced error to test rollback');

            DB::commit();
            return $this->responseCreated('Survey Answer Successfully Sync', $create_survey_answer);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if an error occurs
            return $this->responseServerError('Network Error Please Sync Again', );
        }
    }

}
