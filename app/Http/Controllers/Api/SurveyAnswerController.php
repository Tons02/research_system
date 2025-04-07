<?php

namespace App\Http\Controllers\Api;

use App\Exports\SurveyReports\DemographicExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyAnswerRequest;
use App\Http\Resources\SurveyAnswerResource;
use App\Models\QuestionAnswer;
use App\Models\SurveyAnswer;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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

    public function store(SurveyAnswerRequest $request)
    {
        DB::beginTransaction();

        try {

        $create_survey_answer = SurveyAnswer::create([
            "target_location_id" => $request["target_location_id"],
            "name" => $request["name"],
            "age" => $request["age"],
            "gender" => $request["gender"],
            "address" => $request["address"],
            "contact_number" => $request["contact_number"],
            "date" => $request["date"],
            "family_size" => $request["family_size"],
            "income_class" => $request["income_class"],
            "sub_income_class" => $request["sub_income_class"],
            "monthly_utility_expenses" => $request["monthly_utility_expenses"],
            "sub_monthly_utility_expenses" => $request["sub_monthly_utility_expenses"],
            "educational_attainment" => $request["educational_attainment"],
            "employment_status" => $request["employment_status"],
            "occupation" => $request["occupation"],
            "structure_of_house" => $request["structure_of_house"],
            "ownership_of_house" => $request["ownership_of_house"],
            "questionnaire_answer" => $request["questionnaire_answer"],
            "surveyor_id" => auth('sanctum')->user()->id,
        ]);

        $questionnaire_answers = $request->input('questionnaire_answer', []); // Ensure it's an array

        foreach ($questionnaire_answers as $section) {
            foreach ($section['questions'] as $question) {
                // Handle Grid Type Questions
                if ($question['type'] === 'grid') {
                    foreach ($question['answer'] as $gridAnswer) {
                        if (empty($gridAnswer['rowAnswer'])) {
                            continue; // Skip empty answers
                        }

                        QuestionAnswer::create([
                            'survey_id' => $create_survey_answer->id,
                            'section' => $section['section'],
                            'question_type' => $question['type'],
                            'question' => $question['question'] . ' - ' . $gridAnswer['rowQuestion'],
                            'answer' => $gridAnswer['rowAnswer'],
                        ]);
                    }
                    continue; // Move to the next question
                }

                // Handle Other Question Types (Multiple Choice, Checkbox, Dropdown, Linear Scale, etc.)
                $answers = isset($question['answer']) && is_array($question['answer']) ? $question['answer'] : [];

                foreach ($answers as $answer) {
                    if (empty($answer)) {
                        continue; // Skip empty answers
                    }

                    // Handle "Other" responses correctly
                    $finalAnswer = ($answer === "Other" && isset($question['otherAnswer']))
                        ? (is_array($question['otherAnswer']) ? implode(', ', $question['otherAnswer']) : $question['otherAnswer'])
                        : $answer;

                    QuestionAnswer::create([
                        'survey_id' => $create_survey_answer->id,
                        'section' => $section['section'],
                        'question_type' => $question['type'],
                        'question' => $question['question'],
                        'answer' => $finalAnswer,
                    ]);
                }
            }
        }

            DB::commit();
            return $this->responseCreated('Survey Answer Successfully Synced', $create_survey_answer);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseServerError('Network Error, Please Sync Again');
        }
    }

    public function export(Request $request)
    {
        $target_location_id = $request->query('target_location_id');

        return Excel::download(new DemographicExport($target_location_id), 'Survey Answers.xlsx');
    }


}
