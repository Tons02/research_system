<?php

namespace App\Http\Controllers\Api;

use App\Exports\SurveyReports\DemographicExport;
use App\Exports\SurveyReports\OverAllReport;
use App\Exports\SurveyReports\ResponseClassAB;
use App\Http\Controllers\Controller;
use App\Http\Requests\MultipleSyncSurveyAnswerCountRequest;
use App\Http\Requests\MultipleSyncSurveyAnswerRequest;
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

        $SurveyAnswer = SurveyAnswer::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            // ->orderBy('created_at', 'desc') wag mo kalimutan i uncomment to
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            SurveyAnswerResource::collection($SurveyAnswer);
        } else {
            $SurveyAnswer = SurveyAnswerResource::collection($SurveyAnswer);
        }
        return $this->responseSuccess('Survey answers display successfully', $SurveyAnswer);
    }

    public function mobile_survey(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');
        $per_page = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $target_location_id = $request->query('target_location_id', 0);
        $surveyor_id = $request->query('surveyor_id');
        $date = $request->query('date');

        // Build the base query using Query Builder for safety
        $query = DB::table('survey_answers as s')
            ->leftJoin('target_locations as tl', 'tl.id', '=', 's.target_location_id')
            ->leftJoin('users as surveyor', 'surveyor.id', '=', 's.surveyor_id')
            ->select([
                's.id',
                's.target_location_id',
                's.name',
                's.age',
                's.gender',
                's.address',
                's.contact_number',
                's.date',
                's.family_size',
                's.income_class',
                's.sub_income_class',
                's.monthly_utility_expenses',
                's.sub_monthly_utility_expenses',
                's.educational_attainment',
                's.employment_status',
                's.occupation',
                's.structure_of_house',
                's.ownership_of_house',
                's.house_rent',
                's.surveyor_id',
                's.sync_at',
                's.created_at',
                's.updated_at',
                's.deleted_at',
                'tl.title as tl_title',
                'tl.region',
                'tl.province',
                'tl.city_municipality',
                'tl.sub_municipality',
                'tl.barangay',
                'tl.street',
                DB::raw("CONCAT(
                COALESCE(tl.region, ''), ', ',
                COALESCE(tl.province, ''), ', ',
                COALESCE(tl.city_municipality, ''), ', ',
                COALESCE(tl.sub_municipality, ''), ', ',
                COALESCE(tl.barangay, ''), ', ',
                COALESCE(tl.street, '')
            ) AS target_location"),
                DB::raw("CONCAT(
                COALESCE(surveyor.first_name, ''), ' ',
                COALESCE(surveyor.middle_name, ''), ' ',
                COALESCE(surveyor.last_name, '')
            ) AS surveyor_name")
            ]);

        // Apply filters
        if ($status === 'inactive') {
            $query->whereNotNull('s.deleted_at');
        } else {
            $query->whereNull('s.deleted_at');
        }

        if ($target_location_id) {
            $query->where('s.target_location_id', $target_location_id);
        }

        if ($surveyor_id) {
            $query->where('s.surveyor_id', $surveyor_id);
        }

        if (!empty($date)) {
            $query->whereDate('s.date', $date);
        }

        // Order by sync_at descending
        $query->orderBy('s.sync_at', 'DESC');

        // Execute query based on pagination
        if (!$pagination) {
            $results = $query->get();

            $targetLocations = $results->map(function ($item) {
                return $this->formatSurveyItem($item);
            });
        } else {
            // Get paginated results
            $results = $query->paginate($per_page, ['*'], 'page', $page);

            $targetLocations = [
                'data' => $results->map(function ($item) {
                    return $this->formatSurveyItem($item);
                }),
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ];
        }

        return $this->responseSuccess('Target locations retrieved successfully', $targetLocations);
    }

    private function formatSurveyItem($item)
    {
        return [
            "id" => $item->id,
            "target_location_id" => $item->target_location_id,
            "target_location" => [
                "id" => $item->target_location_id,
                "target_location" => $item->target_location,
                "is_done" => false,
            ],
            "name" => $item->name,
            "age" => $item->age,
            "gender" => $item->gender,
            "address" => $item->address,
            "contact_number" => $item->contact_number,
            "date" => $item->date,
            "family_size" => $item->family_size,
            "income_class" => $item->income_class,
            "sub_income_class" => $item->sub_income_class ?? null,
            "monthly_utility_expenses" => $item->monthly_utility_expenses,
            "sub_monthly_utility_expenses" => $item->sub_monthly_utility_expenses,
            "educational_attainment" => $item->educational_attainment,
            "employment_status" => $item->employment_status,
            "occupation" => $item->occupation,
            "structure_of_house" => $item->structure_of_house,
            "ownership_of_house" => $item->ownership_of_house,
            "house_rent" => $item->house_rent,
            "surveyor" => [
                "id" => $item->surveyor_id,
                "name" => $item->surveyor_name,
            ],
            "sync_at" => $item->sync_at,
            "created_at" => $item->created_at,
            "updated_at" => $item->updated_at ?? null,
            "deleted_at" => $item->deleted_at,
        ];
    }

    public function store(SurveyAnswerRequest $request)
    {
        $user = auth('sanctum')->user();
        $TodayDate = Carbon::now()->format('Y-m-d H:i:s');

        $target_location = TargetLocation::find($request["target_location_id"]);

        // Finalization check
        if (!$target_location->is_final) {
            return $this->responseUnprocessable('', 'You cannot take the survey because it is not finalized. Please contact your supervisor or support.');
        }

        //done checker
        if ($target_location->is_done == 1) {
            return $this->responseUnprocessable('', 'Submit failed: this survey has already been marked as completed.');
        }

        //date checker
        if ($target_location->start_date > $TodayDate) {
            return $this->responseUnprocessable('', 'You cannot take the survey because it is not started yet.');
        }

        $total_surveys = SurveyAnswer::where('target_location_id', $request["target_location_id"])
            ->count();

        // validation for response limit
        if ($target_location->response_limit == $total_surveys) {
            return $this->responseUnprocessable('', 'You have reached the maximum number of responses for this target location.');
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
                "sync_at" => Carbon::now(),
                "created_at" => $request['created_at'],
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

            DB::commit();
            return $this->responseCreated('Survey Answer Successfully Synced', $create_survey_answer);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseServerError('Network Error, Please Sync Again');
        }
    }

    public function multiple_sync(MultipleSyncSurveyAnswerRequest $request)
    {
        $user = auth('sanctum')->user();
        $TodayDate = Carbon::now()->format('Y-m-d H:i:s');

        $surveys = $request->input('survey_answers', []);
        $successfulSyncs = [];
        $failedSyncs = [];

        // Validate all surveys first before processing
        foreach ($surveys as $index => $surveyData) {
            $target_location = TargetLocation::find($surveyData["target_location_id"]);

            if (!$target_location) {
                $failedSyncs[] = [
                    'index' => $index,
                    'error' => 'Target location not found.',
                    'data' => $surveyData
                ];
                continue;
            }

            if (!$target_location->is_final) {
                return response()->json([
                    'message' => 'Survey is not finalized. Please contact your supervisor or support.',
                    'failed_at_index' => $index,
                    'data' => $surveyData
                ], 422);
            }

            // Done checker - STOP EVERYTHING
            if ($target_location->is_done == 1) {
                return response()->json([
                    'message' => 'This survey has already been marked as completed.',
                    'failed_at_index' => $index,
                    'data' => $surveyData
                ], 422);
            }

            // Date checker - STOP EVERYTHING
            if ($target_location->start_date > $TodayDate) {
                return response()->json([
                    'message' => 'Survey has not started yet.',
                    'failed_at_index' => $index,
                    'data' => $surveyData
                ], 422);
            }

            $total_surveys = SurveyAnswer::where('target_location_id', $surveyData["target_location_id"])
                ->count();

            // Response limit validation - STOP EVERYTHING
            if ($target_location->response_limit <= $total_surveys) {
                return response()->json([
                    'message' => 'Maximum number of responses reached for this target location.',
                    'failed_at_index' => $index,
                    'data' => $surveyData
                ], 422);
            }

            // Process valid survey
            DB::beginTransaction();

            try {
                $create_survey_answer = SurveyAnswer::create([
                    "target_location_id" => $surveyData["target_location_id"],
                    "name" => $surveyData["name"],
                    "age" => $surveyData["age"],
                    "gender" => $surveyData["gender"],
                    "address" => $surveyData["address"],
                    "contact_number" => $surveyData["contact_number"],
                    "date" => $surveyData["date"],
                    "family_size" => $surveyData["family_size"],
                    "income_class" => $surveyData["income_class"] ?? null,
                    "sub_income_class" => $surveyData["sub_income_class"] ?? null,
                    "monthly_utility_expenses" => $surveyData["monthly_utility_expenses"],
                    "sub_monthly_utility_expenses" => $surveyData["sub_monthly_utility_expenses"] ?? null,
                    "educational_attainment" => $surveyData["educational_attainment"],
                    "employment_status" => $surveyData["employment_status"] ?? null,
                    "occupation" => $surveyData["occupation"],
                    "structure_of_house" => $surveyData["structure_of_house"],
                    "ownership_of_house" => $surveyData["ownership_of_house"] ?? null,
                    "house_rent" => $surveyData["house_rent"] ?? null,
                    "questionnaire_answer" => $surveyData["questionnaire_answer"],
                    "surveyor_id" => $user->id,
                    "sync_at" => Carbon::now(),
                    "created_at" => $surveyData['created_at'],
                ]);

                $questionnaire_answers = $surveyData['questionnaire_answer'] ?? [];

                foreach ($questionnaire_answers as $section) {
                    foreach ($section['questions'] as $question) {
                        // Handle Grid Type Questions
                        if ($question['type'] === 'grid') {
                            foreach ($question['answer'] as $gridAnswer) {
                                if (empty($gridAnswer['rowAnswer'])) {
                                    continue;
                                }

                                QuestionAnswer::create([
                                    'survey_id' => $create_survey_answer->id,
                                    'income_class' => $surveyData["income_class"] ?? null,
                                    'sub_income_class' => $surveyData["sub_income_class"] ?? null,
                                    'section' => $section['section'],
                                    'question_type' => $question['type'],
                                    'question' => $question['question'] . ' - ' . $gridAnswer['rowQuestion'],
                                    'answer' => $gridAnswer['rowAnswer'],
                                ]);
                            }
                            continue;
                        }

                        // Handle Other Question Types
                        $answers = isset($question['answer']) && is_array($question['answer']) ? $question['answer'] : [];

                        foreach ($answers as $answer) {
                            if (empty($answer)) {
                                continue;
                            }

                            // Handle "Other" responses
                            $finalAnswer = ($answer === "Other" && isset($question['otherAnswer']))
                                ? (is_array($question['otherAnswer']) ? implode(', ', $question['otherAnswer']) : $question['otherAnswer'])
                                : $answer;

                            QuestionAnswer::create([
                                'survey_id' => $create_survey_answer->id,
                                'income_class' => $surveyData["income_class"] ?? null,
                                'sub_income_class' => $surveyData["sub_income_class"] ?? null,
                                'section' => $section['section'],
                                'question_type' => $question['type'],
                                'question' => $question['question'],
                                'answer' => $finalAnswer,
                            ]);
                        }
                    }
                }

                DB::commit();
                $successfulSyncs[] = [
                    'index' => $index,
                    'survey_id' => $create_survey_answer->id,
                    'message' => 'Survey synced successfully'
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                $failedSyncs[] = [
                    'index' => $index,
                    'error' => 'Network Error: ' . $e->getMessage(),
                    'data' => $surveyData
                ];
            }
        }

        // Prepare response
        $response = [
            'successful' => count($successfulSyncs),
            'failed' => count($failedSyncs),
            'total' => count($surveys),
            'success_details' => $successfulSyncs,
            'failed_details' => $failedSyncs
        ];

        if (count($failedSyncs) > 0 && count($successfulSyncs) > 0) {
            return response()->json([
                'message' => 'Partial sync completed. Some surveys failed.',
                'data' => $response
            ], 207); // Multi-Status
        } elseif (count($failedSyncs) > 0) {
            return response()->json([
                'message' => 'All surveys failed to sync.',
                'data' => $response
            ], 422);
        }

        return response()->json([
            'message' => 'All surveys synced successfully.',
            'data' => $response
        ], 201);
    }

    public function export(OverAllRequest $request)
    {
        $target_location_id = $request->query('target_location_id');
        $surveyor_id = $request->query('surveyor_id');
        // Parse and normalize dates
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');
        $status = $request->query('status');

        // return $totalClassC = SurveyAnswer::where('target_location_id', $target_location_id)
        //     ->where('income_class', 'Class AB')
        //     ->when($surveyor_id, function ($query) use ($surveyor_id) {
        //         $query->where('surveyor_id', $surveyor_id);
        //     })
        //     ->when($start_date && $end_date, function ($query) use ($start_date, $end_date) {
        //         $query->whereDate('date', '>=', $start_date)
        //             ->whereDate('date', '<=', $end_date);
        //     })
        //     ->when($start_date && !$end_date, function ($query) use ($start_date) {
        //         $query->whereDate('date', '>=', $start_date);
        //     })
        //     ->when(!$start_date && $end_date, function ($query) use ($end_date) {
        //         $query->whereDate('date', '<=', $end_date);
        //     })
        //     ->count();

        return Excel::download(new OverAllReport($target_location_id, $surveyor_id, $start_date, $end_date, $status),  ' Survey Answers.xlsx');
    }
}
