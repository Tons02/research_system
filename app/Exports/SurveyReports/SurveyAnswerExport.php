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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyAnswerExport implements FromCollection, WithMapping, WithHeadings, WithTitle, WithEvents, WithStyles
{
    protected $target_location_id, $surveyor_id, $from_date, $to_date, $status;

    public function __construct($target_location_id, $surveyor_id, $from_date, $to_date, $status)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->status = $status;
    }

    public function collection()
    {
        $target_location_id = $this->target_location_id;

        $data = QuestionAnswer::query()
            ->join('survey_answers', 'question_answers.survey_id', '=', 'survey_answers.id')
            ->select(
                'question_answers.section',
                'survey_answers.target_location_id',
                'survey_answers.date',
                'question_answers.question',
                'question_answers.answer'
            )
            ->when($target_location_id, function ($query) use ($target_location_id) {
                return $query->where('survey_answers.target_location_id', $target_location_id);
            })
            ->when($this->surveyor_id, function ($query) {
                $query->where('survey_answers.surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('survey_answers.date', [$this->from_date, $this->to_date]);
            })
            ->orderBy('survey_answers.date', 'asc')
            ->orderBy('question_answers.id', 'asc')
            ->get();

            $groupedByDate = [];

            foreach ($data as $item) {
                $date = $item->date;
                $section = $item->section ?? 'Uncategorized';

                if (!isset($groupedByDate[$date])) {
                    $groupedByDate[$date] = [
                        'date' => $date,
                        'sections' => []
                    ];
                }

                if (!isset($groupedByDate[$date]['sections'][$section])) {
                    $groupedByDate[$date]['sections'][$section] = [
                        'section' => $section,
                        'questions' => []
                    ];
                }

                $groupedByDate[$date]['sections'][$section]['questions'][] = [
                    'question' => $item->question,
                    'answer' => $item->answer
                ];
            }

            // Reformat to clean output (remove associative keys)
            $finalResult = [];

            foreach ($groupedByDate as $dateGroup) {
                $sectionsArray = [];

                foreach ($dateGroup['sections'] as $sectionData) {
                    $sectionsArray[] = $sectionData;
                }

                $finalResult[] = [
                    'date' => $dateGroup['date'],
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
            'Question',
            'Answers',
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
                $sheet->setCellValue('B1', 'Answer');

                $sheet->getStyle("A1:B1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Century Gothic'],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9D9D9']
                    ]
                ]);

                $row = 2;

                $dates = $this->collection(); // This now returns a structure grouped by date

                foreach ($dates as $dateGroup) {
                    // Write Date header
                    $sheet->setCellValue("A{$row}", "# Date: {$dateGroup['date']}");
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 13, 'name' => 'Century Gothic'],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFCCCCFF']
                        ]
                    ]);
                    $row++;

                    foreach ($dateGroup['sections'] as $section) {
                        // Write Section
                        $sheet->setCellValue("A{$row}", "Section: {$section['section']}");
                        $sheet->mergeCells("A{$row}:B{$row}");
                        $sheet->getStyle("A{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 12, 'name' => 'Century Gothic'],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE6E6E6']
                            ]
                        ]);
                        $row++;

                        foreach ($section['questions'] as $qa) {
                            $sheet->setCellValue("A{$row}", $qa['question']);
                           $value = $qa['answer'];
                            if ((is_numeric($value) && strlen((string) $value) > 11) || str_starts_with($value, '+')) {
                                $sheet->setCellValueExplicit("B{$row}", (string) $value, DataType::TYPE_STRING);
                            } else {
                                $sheet->setCellValue("B{$row}", $value);
                            }
                            $sheet->getStyle("A{$row}:B{$row}")->getFont()->setName('Century Gothic')->setSize(10);
                            $row++;
                        }

                        // Optional space after each section
                        $row++;
                    }

                    // Optional space after each date
                    $row++;
                }


                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(100);
                $sheet->getColumnDimension('B')->setWidth(40);

                $sheet->getStyle('B')->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);


                // Apply borders to all cells with content
                $lastRow = $row - 1;
                $sheet->getStyle("A1:B{$lastRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }
}
