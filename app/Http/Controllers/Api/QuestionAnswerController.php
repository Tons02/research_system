<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionAnswerResource;
use App\Models\QuestionAnswer;
use App\Models\TargetLocation;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionAnswerController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $type = $request->query('type');

        $from_date = $request->query('from_date') ? $request->query('from_date') . ' 00:00:00' : '2023-06-11 00:00:00';
        $to_date = $request->query('to_date') ? $request->query('to_date') . ' 23:59:59' : '2055-06-11 23:59:59';
        $target_location = $request->query('target_location');

        $query = QuestionAnswer::query()
            ->join('survey_answers', 'question_answers.survey_id', '=', 'survey_answers.id')
            ->select(
                'survey_answers.target_location_id',
                'question_answers.question',
                'question_answers.answer',
                DB::raw('COUNT(question_answers.id) as answer_count')
            )
            ->whereBetween('question_answers.updated_at', [$from_date, $to_date])
            ->when($target_location, function ($query) use ($target_location) {
                return $query->where('survey_answers.target_location_id', $target_location);
            })
            ->groupBy('survey_answers.target_location_id', 'question_answers.question', 'question_answers.answer')
            ->get();

        // Organize data into the desired structure
        $formattedData = $query->groupBy('target_location_id')->map(function ($items, $locationId) {
            return [
                'target_location' => $locationId ? "Location ID: {$locationId}" : "All Location",
                'questions' => $items->groupBy('question')->map(function ($answers, $question) {
                    return [
                        'question' => $question,
                        'answers' => $answers->map(function ($answer) {
                            return [
                                'answer' => $answer->answer,
                                'count' => $answer->answer_count
                            ];
                        })->values()->toArray()
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();

        return response()->json([
            'message' => 'Question answers display successfully',
            'result' => [
                'data' => $formattedData
            ]
        ]);
    }




}
