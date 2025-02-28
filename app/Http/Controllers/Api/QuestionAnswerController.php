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

    public function index(Request $request){
        $type = $request->query('type');

        $from_date = $request->query('from_date')
            ? $request->query('from_date') . ' 00:00:00'
            : '2023-06-11 00:00:00';

        $to_date = $request->query('to_date')
            ? $request->query('to_date') . ' 23:59:59'
            : '2055-06-11 23:59:59';
        $reports = $request->query('reports') ?? 'updated_at';
        $target_location = $request->query('target_location');

        $QuestionAnswers = QuestionAnswer::
        when($reports === 'updated_at', function($query) use ($from_date, $to_date) {
            $query->where('updated_at', '>=', $from_date)
            ->where('updated_at', '<=', $to_date);
        })
        ->when($type === "count", function ($query) {
            $query->select('question', 'answer', 'survey_id', DB::raw('count(*) as answer_count'))
                  ->groupBy('question', 'answer', 'survey_id');
        })
        ->when($type === "excel", function ($query) {
            $query->select('survey_id', 'question', 'answer')
            ->groupBy('survey_id', 'question', 'answer');
        })
        ->useFilters()
        ->dynamicPaginate();


        // Fetch the TargetLocation with the related form and only the `sections` column
        $Questionnaire = TargetLocation::with(['form' => function ($query) {
            $query->select('id', 'sections'); // Select only the `sections` column from the `form` table
        }])->where('id', $target_location)->first();

        // Check if the record and relationship exist
        if (!$Questionnaire || !$Questionnaire->form) {
            return null; // Handle the case where no record or relationship is found
        }

        // Get the sections from the form
        $sections = $Questionnaire->form->sections;

        // Initialize an array to store the formatted questions
        $formattedQuestions = [];

        // Loop through each section in the sections
        foreach ($sections as $section) {
            if (isset($section['questions']) && is_array($section['questions'])) {
                foreach ($section['questions'] as $question) {
                    // Initialize the question with zero counts
                    $formattedQuestions[$question['question']] = [
                        'question' => $question['question'],
                        'answers' => []
                    ];

                    // Set each option's count to 0
                    if (isset($question['options']) && is_array($question['options'])) {
                        foreach ($question['options'] as $option) {
                            $formattedQuestions[$question['question']]['answers'][] = [
                                'answer' => $option,
                                'count' => 0
                            ];
                        }
                    }
                }
            }
        }

        if ($type === "count") {
            $result = [];
            $stores = [];

            foreach ($QuestionAnswers as $qa) {
                $question = $qa->question;
                $target_location = $qa->survey->target_location->target_location ?? "All Location";

                if (!isset($stores[$target_location])) {
                    $stores[$target_location] = [
                        'target_location' => $target_location,
                        'questions' => []
                    ];
                }

                if (!isset($stores[$target_location]['questions'][$question])) {
                    $stores[$target_location]['questions'][$question] = [
                        'question' => $question,
                        'answers' => []
                    ];
                }

                // Ensure all possible answers from $formattedQuestions are included only once
                if (isset($formattedQuestions[$question])) {
                    $existingAnswers = array_column($stores[$target_location]['questions'][$question]['answers'], 'answer');

                    foreach ($formattedQuestions[$question]['answers'] as $formattedAnswer) {
                        $answerText = isset($formattedAnswer['answer']['option'])
                            ? $formattedAnswer['answer']['option']
                            : (is_array($formattedAnswer['answer']) ? json_encode($formattedAnswer['answer']) : $formattedAnswer['answer']);

                        if (!in_array($answerText, $existingAnswers)) {
                            $stores[$target_location]['questions'][$question]['answers'][] = [
                                'answer' => $answerText,
                                'count' => 0 // Default count to 0
                            ];
                            $existingAnswers[] = $answerText; // Add to existing answers to prevent duplication
                        }
                    }
                }

                // Extract the actual answer text from $qa
                $answer = isset($qa->answer['option']) ? $qa->answer['option'] : (is_array($qa->answer) ? json_encode($qa->answer) : $qa->answer);

                // Update the count for matching answers
                foreach ($stores[$target_location]['questions'][$question]['answers'] as &$existingAnswer) {
                    if ($existingAnswer['answer'] === $answer) {
                        $existingAnswer['count'] += $qa->answer_count;
                        break;
                    }
                }
            }

            // Convert associative array to indexed array
            $result = [];
            foreach ($stores as $store) {
                $store['questions'] = array_values($store['questions']); // Convert questions to indexed array
                $result[] = $store;
            }

            return $this->responseCreated('Question Answer count Successfully', ['data' => $result]);
        }

        QuestionAnswerResource::collection($QuestionAnswers);

        $groupedData = $QuestionAnswers->groupBy('survey_id')->map(function ($group) {
            return [
                'survey_id' => $group->first()->survey_id,
                'target_location' => $group->first()->survey->target_location->target_location ?? 'Store',
                'name' => $group->first()->survey->name,
                'questions' => $group->map(function ($item) {
                    return [
                        'question' => $item->question,
                        'answer' => $item->answer,
                    ];
                })->values()
            ];
        })->values();

        $response = [
                'data' => $groupedData
        ];

        return $this->responseCreated('Question Answer excel Successfully', $response);
    }
}
