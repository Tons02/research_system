<?php

namespace App\Exports\SurveyReports;

use App\Models\QuestionAnswer;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResponseClassC implements FromCollection, WithMapping, WithHeadings, WithTitle, WithEvents, WithStyles
{
    protected $target_location_id;


    public function __construct($target_location_id)
    {
        $this->target_location_id = $target_location_id;
    }

    public function collection()
    {
        $target_location_id = $this->target_location_id;

        $data = QuestionAnswer::query()
            ->join('survey_answers', 'question_answers.survey_id', '=', 'survey_answers.id')
            ->select(
                'question_answers.section',
                'survey_answers.target_location_id',
                'question_answers.question',
                'question_answers.answer',
                DB::raw('COUNT(question_answers.id) as answer_count'),
                DB::raw('MIN(question_answers.id) as min_id') // Include for ordering
            )
            ->where('question_answers.income_class', 'Class C')
            ->when($target_location_id, function ($query) use ($target_location_id) {
                return $query->where('survey_answers.target_location_id', $target_location_id);
            })
            ->groupBy(
                'question_answers.section',
                'survey_answers.target_location_id',
                'question_answers.question',
                'question_answers.answer'
            )
            ->orderBy('min_id', 'asc')
            ->get();


        // Transform the data into the hierarchical structure
        $sections = [];

        foreach ($data as $item) {
            $section = $item->section ?? 'Uncategorized';
            $question = $item->question;

            if (!isset($sections[$section])) {
                $sections[$section] = [
                    'section' => $section,
                    'questions' => []
                ];
            }

            $existingQuestionKey = null;
            foreach ($sections[$section]['questions'] as $key => $q) {
                if ($q['question'] === $question) {
                    $existingQuestionKey = $key;
                    break;
                }
            }

            if ($existingQuestionKey !== null) {
                $sections[$section]['questions'][$existingQuestionKey]['answers'][] = [
                    'answer' => $item->answer,
                    'count' => $item->answer_count
                ];
            } else {
                $sections[$section]['questions'][] = [
                    'question' => $question,
                    'answers' => [
                        [
                            'answer' => $item->answer,
                            'count' => $item->answer_count
                        ]
                    ]
                ];
            }
        }

        return collect(array_values($sections));
    }

    public function title(): string
    {
        return 'SURVEY RESPONSES CLASS C';
    }

    public function headings(): array
    {
        return [
            'Question',
            'Answers',
            'Count'
        ];
    }

    public function map($section): array
    {
        // This won't be used directly since we're building custom rows in registerEvents
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'name' => 'Century Gothic']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $row = 2; // Start from row 2 (after headers)

                // Set column headers
                $sheet->setCellValue('A1', 'Question');
                $sheet->setCellValue('B1', 'Answers');
                $sheet->setCellValue('C1', 'Count');

                // Get the transformed data
                $sections = $this->collection();

                foreach ($sections as $section) {
                    // Write section header
                    $sheet->setCellValue("A{$row}", "# Section: {$section['section']}");
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 13, 'name' => 'Century Gothic'],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFD9D9D9']
                        ]
                    ]);
                    $row++;

                    foreach ($section['questions'] as $question) {
                        // Write question
                        $sheet->setCellValue("A{$row}", $question['question']);
                        $sheet->getStyle("A{$row}")->getFont()->setBold(true)
                            ->setName('Century Gothic')
                            ->setSize(10);
                        $sheet->mergeCells("A{$row}:C{$row}");
                        $row++;

                        // Write answers
                        foreach ($question['answers'] as $answer) {
                            $sheet->setCellValue("B{$row}", $answer['answer']);
                            $sheet->getStyle("B{$row}")->getFont()
                                ->setName('Century Gothic')
                                ->setSize(9);
                            $sheet->setCellValue("C{$row}", $answer['count']);
                            $sheet->getStyle("C{$row}")->getFont()->setName('Century Gothic')
                                ->setSize(9);
                            $row++;
                        }

                        // Add empty row between questions
                        $row++;
                    }
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(50);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(15);

                // Apply borders to all cells with content
                $lastRow = $row - 1;
                $sheet->getStyle("A1:C{$lastRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }
}
