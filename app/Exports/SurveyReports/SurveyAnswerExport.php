<?php

namespace App\Exports\SurveyReports;

use App\Models\QuestionAnswer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyAnswerExport implements FromCollection, WithHeadings, WithTitle, WithEvents, WithStyles
{
    protected $target_location_id, $surveyor_id, $start_date, $end_date, $status;

    public function __construct($target_location_id, $surveyor_id, $start_date, $end_date, $status)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->status = $status;
    }

    public function collection()
    {
        $target_location_id = $this->target_location_id;

        $data = QuestionAnswer::query()
            ->join('survey_answers', 'question_answers.survey_id', '=', 'survey_answers.id')
            ->join('users', 'survey_answers.surveyor_id', '=', 'users.id') // join surveyor
            ->select(
                'survey_answers.id as survey_id',
                DB::raw("CASE
                        WHEN survey_answers.name IS NULL OR survey_answers.name = ''
                        THEN 'N/A'
                        ELSE survey_answers.name
                END AS name"),
                'question_answers.section',
                'survey_answers.target_location_id',
                'survey_answers.date',
                'survey_answers.surveyor_id', // ✅ include surveyor id
                DB::raw("CONCAT(users.first_name, ' ', COALESCE(users.middle_name, ''), ' ', users.last_name) as surveyor_name"), // ✅ concat full name
                'question_answers.question',
                'question_answers.answer'
            )
            ->when($target_location_id, function ($query) use ($target_location_id) {
                return $query->where('survey_answers.target_location_id', $target_location_id);
            })
            ->when($this->surveyor_id, function ($query) {
                $query->where('survey_answers.surveyor_id', $this->surveyor_id);
            })
            ->when($this->start_date && $this->end_date, function ($query) {
                $query->whereDate('date', '>=', $this->start_date)
                    ->whereDate('date', '<=', $this->end_date);
            })
            ->when($this->start_date && !$this->end_date, function ($query) {
                $query->whereDate('date', '>=', $this->start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) {
                $query->whereDate('date', '<=', $this->end_date);
            })
            ->orderBy('survey_answers.date', 'asc')
            ->orderBy('question_answers.id', 'asc')
            ->get();

        // group like before
        $groupedBySurvey = [];
        foreach ($data as $item) {
            $surveyId = $item->survey_id;
            $section = $item->section ?? 'Uncategorized';

            if (!isset($groupedBySurvey[$surveyId])) {
                $groupedBySurvey[$surveyId] = [
                    'survey_id' => $surveyId,
                    'name' => $item->name,
                    'target_location_id' => $item->target_location_id,
                    'date' => $item->date,
                    'surveyor_id' => $item->surveyor_id,
                    'surveyor' => trim(preg_replace('/\s+/', ' ', $item->surveyor_name)),
                    'sections' => []
                ];
            }

            if (!isset($groupedBySurvey[$surveyId]['sections'][$section])) {
                $groupedBySurvey[$surveyId]['sections'][$section] = [
                    'section' => $section,
                    'questions' => []
                ];
            }

            $groupedBySurvey[$surveyId]['sections'][$section]['questions'][] = [
                'question' => $item->question,
                'answer' => $item->answer ?? 'N/A',
            ];
        }

        // flatten
        $finalResult = [];
        foreach ($groupedBySurvey as $surveyGroup) {
            $sectionsArray = [];
            foreach ($surveyGroup['sections'] as $sectionData) {
                $sectionsArray[] = $sectionData;
            }

            $finalResult[] = [
                'survey_id' => $surveyGroup['survey_id'],
                'name' => $surveyGroup['name'],
                'target_location_id' => $surveyGroup['target_location_id'],
                'date' => $surveyGroup['date'],
                'surveyor_id' => $surveyGroup['surveyor_id'],
                'surveyor' => $surveyGroup['surveyor'],
                'sections' => $sectionsArray
            ];
        }

        return collect($finalResult);
    }




    public function title(): string
    {
        return 'SURVEY ANSWERS';
    }

    public function headings(): array
    {
        return [
            'Name',
            'Survey ID',
            'Date',
            'Section',
            'Question',
            'Answer',
            'Researcher ID',
            'Researcher Name',
        ];
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

                // Set headers
                $sheet->setCellValue('A1', 'Name');
                $sheet->setCellValue('B1', 'Survey ID');
                $sheet->setCellValue('C1', 'Date');
                $sheet->setCellValue('D1', 'Section');
                $sheet->setCellValue('E1', 'Question');
                $sheet->setCellValue('F1', 'Answer');
                $sheet->setCellValue('G1', 'Researcher ID');
                $sheet->setCellValue('H1', 'Researcher');

                // Style headers
                $sheet->getStyle("A1:H1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Century Gothic'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9D9D9']
                    ]
                ]);

                // Start writing from row 2
                $row = 2;

                // Define alternating colors
                $colors = ['DCE6F1', 'DFFFD6'];
                $previousSurveyId = null;
                $currentColorIndex = 0;

                // Your data collection
                $respondents = $this->collection();

                foreach ($respondents as $respondent) {
                    $surveyId = $respondent['survey_id'];

                    // Alternate background color if survey ID changes
                    if ($surveyId !== $previousSurveyId) {
                        $currentColorIndex = 1 - $currentColorIndex;
                        $previousSurveyId = $surveyId;
                    }

                    $bgColor = $colors[$currentColorIndex];

                    $name = $respondent['name'];

                    // Loop through sections and questions
                    foreach ($respondent['sections'] as $section) {
                        foreach ($section['questions'] as $qa) {
                            $question = $qa['question'];
                            $answer = $qa['answer'];

                            $answer = ($answer === '' || $answer === null) ? 'N/A' : $answer;
                            // Obfuscate Name answer
                            if (strtolower(trim($question)) === 'name') {
                                $answer = $name;
                            }

                            // Set each row
                            $sheet->setCellValue("A{$row}", $name);
                            $sheet->setCellValue("B{$row}", $surveyId);

                            $sheet->setCellValue("C{$row}", Carbon::parse($respondent['date'])->format('M d, Y h:i A'));
                            $sheet->setCellValue("D{$row}", $section['section']);
                            $sheet->setCellValue("E{$row}", $question);

                            // Phone number formatting
                            if ((is_numeric($answer) && strlen((string)$answer) > 11) || str_starts_with($answer, '+')) {
                                $sheet->setCellValueExplicit("F{$row}", (string) $answer, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            } else {
                                $sheet->setCellValue("F{$row}", $answer);
                            }

                            // ✅ New surveyor columns
                            $sheet->setCellValue("G{$row}", $respondent['surveyor_id']);
                            $sheet->setCellValue("H{$row}", $respondent['surveyor']);


                            // Apply styles
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                                'font' => ['name' => 'Century Gothic', 'size' => 10],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => $bgColor]
                                ]
                            ]);

                            $row++;
                        }
                    }
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(110);
                $sheet->getColumnDimension('F')->setWidth(40);
                $sheet->getColumnDimension('G')->setWidth(17);  // Researcher ID
                $sheet->getColumnDimension('H')->setWidth(40);  // Researcher Name

                // Border styling for all cells with data
                $lastRow = $row - 1;
                $sheet->getStyle("A1:H{$lastRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }
}
