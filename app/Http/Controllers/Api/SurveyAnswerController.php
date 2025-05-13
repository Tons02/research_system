<?php

namespace App\Http\Controllers\Api;

use App\Exports\SurveyReports\DemographicExport;
use App\Exports\SurveyReports\OverAllReport;
use App\Exports\SurveyReports\ResponseClassAB;
use App\Http\Controllers\Controller;
use App\Http\Requests\OverAllRequest;
use App\Http\Requests\SurveyAnswerRequest;
use App\Http\Resources\SurveyAnswerResource;
use App\Models\QuestionAnswer;
use App\Models\SurveyAnswer;
use App\Models\TargetLocation;
use Carbon\Carbon;
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
        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');

        if ($from_date) {
            $from_date = Carbon::parse($from_date)->startOfDay();
        }

        if ($to_date) {
            $to_date = Carbon::parse($to_date)->endOfDay();
        }

        $SurveyAnswer = SurveyAnswer::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->when($from_date != null && $to_date != null, function ($query) use ($from_date, $to_date) {
                return $query->whereBetween('date', [$from_date, $to_date]);
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
        $user = auth('sanctum')->user();

        // Check if user has access to the location (either active or historical)
        $activeLocation = $user->loadMissing('target_locations_users')
            ->target_locations_users
            ->firstWhere('id', $request["target_location_id"]);

        $historicalLocation = $user->loadMissing('target_locations_users_history')
            ->target_locations_users_history
            ->firstWhere('id', $request["target_location_id"]);

        // Access validation
        if (! $activeLocation && ! $historicalLocation) {
            return $this->responseUnprocessable('', 'You cannot take survey on this location, only on your tagged location.');
        }

        // Prioritize historical record if it exists, otherwise use active
        $location = $historicalLocation ?: $activeLocation;

        // Finalization check
        if (! $location->is_final) {
            return $this->responseUnprocessable('', 'You cannot take the survey because it is not finalized. Please contact your supervisor or support.');
        }

        // Count how many surveys the user has already submitted
        $total_survey_of_surveyor = SurveyAnswer::where('target_location_id', $request["target_location_id"])
            ->where('surveyor_id', $user->id)
            ->count();

        // Response limit validation
        $pivotLimit = optional($location->pivot)->response_limit;
        $locationLimit = $location->response_limit;

        if ($pivotLimit === $locationLimit && $total_survey_of_surveyor > $locationLimit) {
            $target_location = TargetLocation::find($location->id);

            if (! $target_location) {
                return $this->responseUnprocessable('', 'Invalid ID provided. Please check the ID and try again.');
            }

            $target_location->update(['is_done' => 1]);

            return $this->responseUnprocessable('', 'Survey is done');
        }


        if ($total_survey_of_surveyor >= $locationLimit) {
            return $this->responseUnprocessable('', 'Survey is done');
        }

        // If user exceeded either limit, stop them
        if ($pivotLimit !== null && $total_survey_of_surveyor >= $pivotLimit) {
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
                "house_rent" => $request["house_rent"],
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

            if ($total_survey_of_surveyor_after_creating === $pivotLimit) {
                // Find the target location pivot record
                $user->target_locations_users()
                    ->updateExistingPivot($request["target_location_id"], [
                        'is_done' => 1
                    ]);
            }

            // For closing the survey
            $total_survey_on_location = SurveyAnswer::where('target_location_id', $request["target_location_id"])
                ->count();

            // Step 2: Check if response limit has been reached globally
            if ($total_survey_on_location >= $location->response_limit) {
                $target_location = TargetLocation::find($location->id);

                if (! $target_location) {
                    return $this->responseUnprocessable('', 'Invalid ID provided. Please check the ID and try again.');
                }

                $target_location->update(['is_done' => 1]);

                DB::commit();
                return $this->responseCreated('Survey Answer Successfully Synced', '');
            }

            DB::commit();
            return $this->responseCreated('Survey Answer Successfully Synced', $create_survey_answer);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseServerError('Network Error, Please Sync Again');
        }
    }

    public function export(OverAllRequest $request)
    {
        $target_location_id = $request->query('target_location_id');
        $surveyor_id = $request->query('surveyor_id');
        $from_date = $request->query('from_date')
            ? Carbon::parse($request->query('from_date'))->startOfDay()
            : Carbon::createFromFormat('m-d-Y', '03-01-2025')->startOfDay();

        $to_date = $request->query('to_date')
            ? Carbon::parse($request->query('to_date'))->endOfDay()
            : Carbon::createFromFormat('m-d-Y', '03-01-2050')->startOfDay();
        $status = $request->query('status');

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


        $survey_count = SurveyAnswer::where('target_location_id', $target_location_id)
            ->when($surveyor_id, function ($query) use ($surveyor_id) {
                $query->where('surveyor_id', $surveyor_id);
            })
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            })
            ->get()->count();

        if ($survey_count <= 0) {
            return $this->responseUnprocessable('', 'No Available Reports.');
        }

        // return DB::table('survey_answers')
        //     ->select('educational_attainment', 'income_class')
        //     ->where('target_location_id', $target_location_id)
        //     ->when($surveyor_id, function ($query) use ($surveyor_id) {
        //         $query->where('surveyor_id', $surveyor_id);
        //     })
        //     ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
        //         $query->whereBetween('created_at', [$from_date, $to_date]);
        //     })
        //     ->get();

        return Excel::download(new OverAllReport($target_location_id, $surveyor_id, $from_date, $to_date, $status),  ' Survey Answers.xlsx');
    }
}
