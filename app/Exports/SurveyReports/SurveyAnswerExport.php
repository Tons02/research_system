<?php

namespace App\Exports\SurveyReports;

use App\Models\QuestionAnswer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SurveyAnswerExport implements WithTitle, WithEvents
{
    protected $target_location_id, $surveyor_id, $start_date, $end_date, $status;

    public function __construct($target_location_id, $surveyor_id, $start_date, $end_date, $status)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id        = $surveyor_id;
        $this->start_date         = $start_date;
        $this->end_date           = $end_date;
        $this->status             = $status;
    }

    public function title(): string
    {
        return 'SURVEY ANSWERS';
    }

    /**
     * Fetch and group all survey data.
     * Returns [ groupedBySurvey[], allQuestions[] ]
     */
    private function fetchData(): array
    {
        $target_location_id = $this->target_location_id;

        $data = QuestionAnswer::query()
            ->join('survey_answers', 'question_answers.survey_id', '=', 'survey_answers.id')
            ->join('users', 'survey_answers.surveyor_id', '=', 'users.id')
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
                'survey_answers.sync_at',
                'survey_answers.surveyor_id',
                DB::raw("CONCAT(users.first_name, ' ', COALESCE(users.middle_name, ''), ' ', users.last_name) as surveyor_name"),
                'question_answers.question',
                'question_answers.answer'
            )
            ->when($target_location_id, fn($q) => $q->where('survey_answers.target_location_id', $target_location_id))
            ->when($this->surveyor_id,  fn($q) => $q->where('survey_answers.surveyor_id', $this->surveyor_id))
            ->when($this->start_date && $this->end_date, fn($q) =>
            $q->whereDate('date', '>=', $this->start_date)->whereDate('date', '<=', $this->end_date))
            ->when($this->start_date && !$this->end_date, fn($q) =>
            $q->whereDate('date', '>=', $this->start_date))
            ->when(!$this->start_date && $this->end_date, fn($q) =>
            $q->whereDate('date', '<=', $this->end_date))
            ->orderBy('survey_answers.date', 'asc')
            ->orderBy('question_answers.id', 'asc')
            ->get();

        $groupedBySurvey = [];
        $allQuestions    = []; // Preserves insertion order across all surveys

        foreach ($data as $item) {
            $surveyId = $item->survey_id;

            if (!isset($groupedBySurvey[$surveyId])) {
                $groupedBySurvey[$surveyId] = [
                    'survey_id'   => $surveyId,
                    'name'        => $item->name,
                    'date'        => $item->date,
                    'sync_at'     => $item->sync_at,
                    'surveyor_id' => $item->surveyor_id,
                    'surveyor'    => trim(preg_replace('/\s+/', ' ', $item->surveyor_name)),
                    'answers'     => [], // question => answer (or array of answers for repeated questions)
                ];
            }

            $question = $item->question;
            $answer   = ($item->answer === '' || $item->answer === null) ? 'N/A' : $item->answer;

            // Override name question with the respondent's name
            if (strtolower(trim($question)) === 'name') {
                $answer = $item->name;
            }

            // Track unique question order globally
            if (!isset($allQuestions[$question])) {
                $allQuestions[$question] = true;
            }

            // Handle duplicate questions (same question, multiple answers) by appending
            if (isset($groupedBySurvey[$surveyId]['answers'][$question])) {
                $existing = $groupedBySurvey[$surveyId]['answers'][$question];
                if (!is_array($existing)) {
                    $groupedBySurvey[$surveyId]['answers'][$question] = [$existing];
                }
                $groupedBySurvey[$surveyId]['answers'][$question][] = $answer;
            } else {
                $groupedBySurvey[$surveyId]['answers'][$question] = $answer;
            }
        }

        return [$groupedBySurvey, array_keys($allQuestions)];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                [$groupedBySurvey, $allQuestions] = $this->fetchData();

                // ── Fixed columns ──────────────────────────────────────────
                $fixedHeaders = [
                    'Researcher ID',
                    'Researcher',
                    'Sync Date',
                    'Survey ID',
                    'Name',
                    'Date',
                ];

                // ── Build full header row ──────────────────────────────────
                $headers = array_merge($fixedHeaders, $allQuestions);

                // Write headers
                foreach ($headers as $colIndex => $header) {
                    $colLetter = $this->columnLetter($colIndex + 1);
                    $sheet->setCellValue("{$colLetter}1", $header);
                }

                $lastCol = $this->columnLetter(count($headers));

                // Style headers
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Century Gothic'],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9D9D9'],
                    ],
                ]);

                // Wrap text & align top for headers
                $sheet->getStyle("A1:{$lastCol}1")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Taller header row height
                $sheet->getRowDimension(1)->setRowHeight(80);

                // ── Write data rows ────────────────────────────────────────
                $colors            = ['DCE6F1', 'DFFFD6'];
                $currentColorIndex = 0;
                $row               = 2;

                foreach ($groupedBySurvey as $survey) {
                    $bgColor = $colors[$currentColorIndex % 2];
                    $currentColorIndex++;

                    // Fixed column values
                    $fixedValues = [
                        $survey['surveyor_id'],
                        $survey['surveyor'],
                        $survey['sync_at'] ? Carbon::parse($survey['sync_at'])->format('M d, Y h:i A') : 'N/A',
                        $survey['survey_id'],
                        $survey['name'],
                        Carbon::parse($survey['date'])->format('M d, Y h:i A'),
                    ];

                    // Write fixed columns
                    foreach ($fixedValues as $colIndex => $value) {
                        $colLetter = $this->columnLetter($colIndex + 1);
                        $sheet->setCellValue("{$colLetter}{$row}", $value);
                    }

                    // Write dynamic question columns
                    foreach ($allQuestions as $qIndex => $question) {
                        $colLetter = $this->columnLetter(count($fixedHeaders) + $qIndex + 1);
                        $answer    = $survey['answers'][$question] ?? 'N/A';

                        // Join multiple answers with a separator
                        if (is_array($answer)) {
                            $answer = implode(' | ', $answer);
                        }

                        // Phone number / long numeric formatting
                        if ((is_numeric($answer) && strlen((string) $answer) > 11) || str_starts_with((string) $answer, '+')) {
                            $sheet->setCellValueExplicit("{$colLetter}{$row}", (string) $answer, DataType::TYPE_STRING);
                        } else {
                            $sheet->setCellValue("{$colLetter}{$row}", $answer);
                        }
                    }

                    // Apply row styling
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => ['name' => 'Century Gothic', 'size' => 10],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $bgColor],
                        ],
                    ]);

                    // Align data rows to top, wrap text, consistent height
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP)
                        ->setWrapText(true);

                    $sheet->getRowDimension($row)->setRowHeight(40);

                    $row++;
                }

                // ── Borders ────────────────────────────────────────────────
                $lastRow = $row - 1;
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // ── Column widths ──────────────────────────────────────────
                $widthMap = [
                    1 => 17,  // Researcher ID
                    2 => 40,  // Researcher
                    3 => 25,  // Sync Date
                    4 => 12,  // Survey ID
                    5 => 25,  // Name
                    6 => 25,  // Date
                ];

                foreach ($widthMap as $colNum => $width) {
                    $sheet->getColumnDimension($this->columnLetter($colNum))->setWidth($width);
                }

                // Dynamic question columns default width
                $totalCols = count($headers);
                for ($i = count($fixedHeaders) + 1; $i <= $totalCols; $i++) {
                    $sheet->getColumnDimension($this->columnLetter($i))->setWidth(35);
                }
            },
        ];
    }

    /**
     * Convert a 1-based column index to an Excel column letter (A, B, ... Z, AA, AB ...).
     */
    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letter    = chr(65 + $remainder) . $letter;
            $index     = (int)(($index - $remainder) / 26);
        }
        return $letter;
    }
}
