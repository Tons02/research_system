<?php

namespace App\Http\Controllers\Api;

use App\Exports\SurveyReports\DemographicExport;
use App\Exports\SurveyReports\OverAllReport;
use App\Exports\SurveyReports\ResponseClassAB;
use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyAnswerRequest;
use App\Http\Resources\SurveyAnswerResource;
use App\Models\QuestionAnswer;
use App\Models\SurveyAnswer;
use App\Models\TargetLocation;
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

        $SurveyAnswer = SurveyAnswer::when($status === "inactive", function ($query) {
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
        $user = auth('sanctum')->user()->load('target_locations_users');

        // Step 1: Filter locations where is_done == 0 and map with target_location_id and response_limit
        $allowedLocations = $user->target_locations_users
            ->filter(function ($item) {
                return $item->pivot->is_done == 0;
            })
            ->mapWithKeys(function ($item) {
                return [
                    $item->pivot->target_location_id => $item->pivot->response_limit
                ];
            });

        // Step 2: Check if the requested location is allowed
        if (!$allowedLocations->has($request["target_location_id"])) {
            return $this->responseUnprocessable('', 'You cannot take survey on this location, only on your tagged location.');
        }

        // Step 3: Get the response limit for this location
        $response_limit = $allowedLocations[$request["target_location_id"]];

        // Step 4: Count how many surveys the user has already submitted
        $total_survey_of_surveyor = SurveyAnswer::where('target_location_id', $request["target_location_id"])
            ->where('surveyor_id', $user->id)
            ->count();

        // Step 5: Compare
        if ($total_survey_of_surveyor >= $response_limit) {
            return $this->responseUnprocessable('', 'You cannot create more surveys than you are tagged for.');
        }


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
                                'income_class' => $request["income_class"], // why is this not working
                                'sub_income_class' => $request["sub_income_class"], // why is this not working
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
                            'income_class' => $request["income_class"],
                            'sub_income_class' => $request["sub_income_class"],
                            'section' => $section['section'],
                            'question_type' => $question['type'],
                            'question' => $question['question'],
                            'answer' => $finalAnswer,
                        ]);
                    }
                }
            }


            $total_survey_of_surveyor_after_creating = SurveyAnswer::where('target_location_id', $request["target_location_id"])
                ->where('surveyor_id', $user->id)
                ->count();

            if ($total_survey_of_surveyor_after_creating === $response_limit) {
                // Find the target location pivot record
                $user->target_locations_users()
                    ->updateExistingPivot($request["target_location_id"], [
                        'is_done' => 1
                    ]);
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

        $location_name = TargetLocation::find($target_location_id);

        if (!$location_name) {
            return $this->responseUnprocessable('', 'Invalid ID provided. Please check the ID and try again.');
        }

        $target_location = trim(implode(', ', array_filter([
            $location_name->province ?? null,
            $location_name->city_municipality ?? null,
            $location_name->sub_municipality ?? null,
            $location_name->barangay ?? null
        ])));

        $survey_count = SurveyAnswer::where('target_location_id', $target_location_id)->get()->count();

        if ($survey_count <= 0) {
            return $this->responseUnprocessable('', 'No Available Reports.');
        }

        return Excel::download(new OverAllReport($target_location_id), $target_location . ' Survey Answers.xlsx');
    }
}
