<?php

namespace App\Exports\SurveyReports;

use App\Models\SurveyAnswer;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DemographicExport implements FromCollection, WithTitle, WithHeadings, WithColumnWidths, WithDefaultStyles, WithStyles, WithMapping, WithEvents, WithColumnFormatting
{
    use ApiResponse;

    protected $target_location_id, $sub_income_class_data, $income_class, $class_counts, $education_data, $employment, $occupation_of_employed, $surveyor_id, $from_date, $to_date, $status;

    public function __construct($target_location_id, $surveyor_id, $from_date, $to_date, $status)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->status = $status;

        // Total count for 'Class C'
        $totalClassC = SurveyAnswer::where('target_location_id', $target_location_id)
            ->where('income_class', 'Class C')
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->count();

        // If total is 0, prevent division by zero
        if ($totalClassC === 0) {
            $this->sub_income_class_data = collect();
            return;
        }

        // Get raw data without rounding
        $rawData = SurveyAnswer::selectRaw("
                sub_income_class,
                COUNT(*) as count")
            ->where('target_location_id', $target_location_id)
            ->where('income_class', 'Class C')
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->groupBy('sub_income_class')
            ->get();

        // Calculate unrounded and rounded percentages
        $percentageData = [];
        $totalRounded = 0;
        foreach ($rawData as $row) {
            $unrounded = ($row->count / $totalClassC) * 100;
            $rounded = floor($unrounded); // or round($unrounded), but we use floor to leave room for adjustments
            $percentageData[] = [
                'sub_income_class' => $row->sub_income_class,
                'count' => $row->count,
                'rounded' => $rounded,
                'remainder' => $unrounded - $rounded,
            ];
            $totalRounded += $rounded;
        }

        // Distribute remaining points (to total 100)
        $difference = 100 - $totalRounded;
        usort($percentageData, fn($a, $b) => $b['remainder'] <=> $a['remainder']);
        for ($i = 0; $i < $difference; $i++) {
            $percentageData[$i]['rounded'] += 1;
        }

        // Format as collection
        $this->sub_income_class_data = collect();
        $totalCount = 0;
        $totalPercent = 0;
        foreach ($percentageData as $item) {
            $totalCount += $item['count'];
            $totalPercent += $item['rounded'];
            $this->sub_income_class_data->push((object)[
                'sub_income_class' => $item['sub_income_class'],
                'count' => $item['count'],
                'percentage' => $item['rounded'],
            ]);
        }

        // Add total row
        $this->sub_income_class_data->push((object)[
            'sub_income_class' => 'TOTAL',
            'count' => $totalCount,
            'percentage' => $totalPercent,
        ]);


        // class C query
        $this->class_counts = SurveyAnswer::where('target_location_id', $target_location_id)
            ->distinct('income_class')
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->count();


        // EDUCATION
        // Predefine all required income classes (including those that might have zero counts)
        $requiredClasses = ['Class AB', 'Class C', 'Class DE'];

        // Get existing classes from the database
        $incomeClasses = DB::table('survey_answers')
            ->select('income_class')
            ->where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->whereNotNull('income_class')
            ->distinct()
            ->pluck('income_class')
            ->toArray();

        // Merge with required classes and remove duplicates
        $incomeClasses = array_unique(array_merge($requiredClasses, $incomeClasses));

        // Get all records with their educational attainment and income class
        $records = DB::table('survey_answers')
            ->select('educational_attainment', 'income_class')
            ->where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->get();

        // Initialize totals for all classes (including zeros)
        $classTotals = array_fill_keys($incomeClasses, 0);

        // Calculate actual totals from records
        foreach ($incomeClasses as $class) {
            $classTotals[$class] = $records->where('income_class', $class)->count();
        }

        $totalRecords = $records->count();

        // Process the data to get statistics per education level
        $educationStats = $records->groupBy('educational_attainment')
            ->map(function ($group) use ($incomeClasses, $classTotals, $totalRecords) {
                $stats = ['education' => $group->first()->educational_attainment];

                foreach ($incomeClasses as $class) {
                    $key = strtolower(str_replace(' ', '_', $class));
                    $count = $group->where('income_class', $class)->count();
                    $stats[$key . '_count'] = $count;
                    $stats[$key . '_percent'] = ($classTotals[$class] > 0) ? round($count * 100 / $classTotals[$class]) : 0;
                }

                $stats['total_count'] = $group->count();
                $stats['total_percent'] = ($totalRecords > 0) ? round($group->count() * 100 / $totalRecords) : 0;

                return $stats;
            })
            ->sortBy(function ($item) {
                $order = [
                    'Post-Graduate' => 1,
                    'Some Post-Graduate' => 2,
                    'College Graduate' => 3,
                    'Some College' => 4,
                    'Vocation/Technical' => 5,
                    'High School Graduate' => 6,
                    'Some High School' => 7,
                    'Elementary Graduate' => 8,
                    'Some Elementary' => 9,
                    'No Formal Schooling' => 10,
                    'Als graduate' => 11
                ];
                return $order[$item['education']] ?? 999;
            })
            ->values();

        // Add totals row (now includes all classes)
        $totals = ['education' => 'TOTAL'];
        foreach ($incomeClasses as $class) {
            $key = strtolower(str_replace(' ', '_', $class));
            $totals[$key . '_count'] = $classTotals[$class]; // Use our precalculated totals
            $totals[$key . '_percent'] = 100;
        }
        $totals['total_count'] = $totalRecords;
        $totals['total_percent'] = 100;

        $educationStats->push($totals);

        $this->education_data = $educationStats;

        // employment data

        // First get the counts for each income class
        $classABTotal = SurveyAnswer::where('income_class', 'CLASS AB')
            ->where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->count();
        $classCTotal = SurveyAnswer::where('income_class', 'CLASS C')
            ->where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->count();
        $classDETotal = SurveyAnswer::where('income_class', 'CLASS DE')
            ->where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->count();

        $overallTotal = SurveyAnswer::where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->count();


        // Get data for each employment status
        $statuses = ['EMPLOYED', 'SELF-EMPLOYED', 'UNEMPLOYED'];

        $report = collect($statuses)->map(function ($status) use ($classABTotal, $classCTotal, $classDETotal, $overallTotal, $target_location_id) {
            $classABCount = SurveyAnswer::where('income_class', 'CLASS AB')
                ->where('employment_status', $status)
                ->where('target_location_id', $target_location_id)
                ->when($this->surveyor_id, function ($query) {
                    $query->where('surveyor_id', $this->surveyor_id);
                })
                ->when($this->from_date && $this->to_date, function ($query) {
                    $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
                })
                ->count();

            $classCCount = SurveyAnswer::where('income_class', 'CLASS C')
                ->where('employment_status', $status)
                ->where('target_location_id', $target_location_id)
                ->when($this->surveyor_id, function ($query) {
                    $query->where('surveyor_id', $this->surveyor_id);
                })
                ->when($this->from_date && $this->to_date, function ($query) {
                    $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
                })
                ->count();

            $classDECount = SurveyAnswer::where('income_class', 'CLASS DE')
                ->where('employment_status', $status)
                ->where('target_location_id', $target_location_id)
                ->when($this->surveyor_id, function ($query) {
                    $query->where('surveyor_id', $this->surveyor_id);
                })
                ->when($this->from_date && $this->to_date, function ($query) {
                    $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
                })
                ->count();

            $totalCount = SurveyAnswer::where('employment_status', $status)
                ->where('target_location_id', $target_location_id)
                ->when($this->surveyor_id, function ($query) {
                    $query->where('surveyor_id', $this->surveyor_id);
                })
                ->when($this->from_date && $this->to_date, function ($query) {
                    $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
                })
                ->count();

            return [
                'employment' => $status,
                'class_ab_count' => $classABCount,
                'class_ab_percent' => $classABTotal > 0 ? round(($classABCount / $classABTotal) * 100) : 0,
                'class_c_count' => $classCCount,
                'class_c_percent' => $classCTotal > 0 ? round(($classCCount / $classCTotal) * 100) : 0,
                'class_de_count' => $classDECount,
                'class_de_percent' => $classDETotal > 0 ? round(($classDECount / $classDETotal) * 100) : 0,
                'total_count' => $totalCount,
                'total_percent' => $overallTotal > 0 ? round(($totalCount / $overallTotal) * 100) : 0,
            ];
        });

        // Add the totals row at the end
        $report->push([
            'employment' => 'Total',
            'class_ab_count' => $classABTotal,
            'class_ab_percent' => 100,
            'class_c_count' => $classCTotal,
            'class_c_percent' => 100,
            'class_de_count' => $classDETotal,
            'class_de_percent' => 100,
            'total_count' => $overallTotal,
            'total_percent' => 100,
        ]);

        $this->employment = $report;


        // employed occupation
        $bindings = [];
        $subConditions = "employment_status = 'Employed' AND target_location_id = ?";
        $bindings[] = $target_location_id;

        if ($this->surveyor_id) {
            $subConditions .= " AND surveyor_id = ?";
            $bindings[] = $this->surveyor_id;
        }

        if ($this->from_date && $this->to_date) {
            $subConditions .= " AND created_at BETWEEN ? AND ?";
            $bindings[] = $this->from_date;
            $bindings[] = $this->to_date;
        }

        $sql = "
            SELECT
                occupation,
                COUNT(*) as count,
                CONCAT(ROUND((COUNT(*) * 100.0 / (
                    SELECT COUNT(*)
                    FROM survey_answers
                    WHERE $subConditions
                )))) as percentage
            FROM
                survey_answers
            WHERE
                employment_status = 'Employed'
                AND target_location_id = ?";

        $bindings[] = $target_location_id;

        if ($this->surveyor_id) {
            $sql .= " AND surveyor_id = ?";
            $bindings[] = $this->surveyor_id;
        }

        if ($this->from_date && $this->to_date) {
            $sql .= " AND created_at BETWEEN ? AND ?";
            $bindings[] = $this->from_date;
            $bindings[] = $this->to_date;
        }

        $sql .= "
            GROUP BY occupation
            ORDER BY count DESC
        ";

        $results_employed_occupation = DB::select($sql, $bindings);


        // Calculate the total count and sum of percentages (should be ~100%)
        $total_count = 0;
        foreach ($results_employed_occupation as $row) {
            $total_count += $row->count;
        }

        // Add a TOTAL row at the end
        $total_row = (object)[
            'occupation' => 'TOTAL',
            'count' => $total_count,
            'percentage' => '100'
        ];

        // Append the total to the results
        $results_employed_occupation[] = $total_row;

        $this->occupation_of_employed = $results_employed_occupation;
    }



    public function collection()
    {
        $target_location_id = $this->target_location_id;

        $totalCount = SurveyAnswer::where('target_location_id', $target_location_id)->count();

        $data = SurveyAnswer::selectRaw("income_class, COUNT(*) as total, (COUNT(*) / $totalCount) * 100 as percentage")
            ->where('target_location_id', $target_location_id)
            ->when($this->surveyor_id, function ($query) {
                $query->where('surveyor_id', $this->surveyor_id);
            })
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->groupBy('income_class')
            ->orderBy('income_class', 'asc')
            ->get();

        // Round total and percentage to remove decimals
        $data->each(function ($item) {
            $item->total = round($item->total);
            $item->percentage = round($item->percentage);
        });

        $totalN = $data->sum('total');
        $totalPercentage = $data->sum('percentage');

        $data->push((object)[
            'income_class' => 'TOTAL',
            'total' => $totalN,
            'percentage' => round($totalPercentage),
        ]);

        return $data;
    }


    public function title(): string
    {
        return 'DEMOGRAPHICS';
    }

    public function headings(): array
    {

        return [
            ["DEMOGRAPHICS"],
            [],
            [],
            [
                'INCOME',
                'N',
                '%'
            ]
        ];
    }

    public function map($demographics): array
    {
        return [
            $demographics->income_class,
            $demographics->total,
            $demographics->percentage
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 🟢 First Dataset (Main Demographics)
                $firstTableStartRow = 4; // Assuming headers start at row 4
                $demographicsCount = $this->class_counts; // Get the count of demographics data
                $firstTableEndRow = $firstTableStartRow + $demographicsCount + 1;

                // Apply gray styling to the last row of the first dataset
                $sheet->getStyle("A{$firstTableEndRow}:C{$firstTableEndRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BFBFBF'], // Gray color
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 🟡 Second Dataset (Sub Income Class)
                $secondTableStartRow = 10;
                $sheet->setCellValue('A10', 'CLASS C');
                $sheet->setCellValue('B10', 'N');
                $sheet->setCellValue('C10', '%');

                $sheet->getStyle('A10:C10')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B050'], // Green header
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Insert sub-income class data dynamically
                $row = 11;
                foreach ($this->sub_income_class_data as $subIncome) {
                    $sheet->setCellValue("A{$row}", $subIncome->sub_income_class);
                    $sheet->setCellValue("B{$row}", $subIncome->count);
                    $sheet->setCellValue("C{$row}", $subIncome->percentage);

                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['size' => 10, 'name' => 'Century Gothic'],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $row++;
                }

                // Apply gray styling to the last row of the second dataset
                $lastRow = $row - 1;
                $sheet->getStyle("A{$lastRow}:C{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BFBFBF'],
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Apply borders to both tables
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                        'font' => [
                            'name' => 'Century Gothic',
                            'size' => 10,
                        ]
                    ],
                ];
                $sheet->getStyle("A{$firstTableStartRow}:C{$firstTableEndRow}")->applyFromArray($borderStyle);
                $sheet->getStyle("A10:C{$lastRow}")->applyFromArray($borderStyle);

                // Education Data
                $sheet->setCellValue('A17', 'EDUCATION');
                $sheet->setCellValue('B17', 'CLASS AB');
                $sheet->setCellValue('C17', '%');
                $sheet->setCellValue('D17', 'CLASS C');
                $sheet->setCellValue('E17', '%');
                $sheet->setCellValue('F17', 'CLASS DE');
                $sheet->setCellValue('G17', '%');
                $sheet->setCellValue('H17', 'TOTAL');
                $sheet->setCellValue('I17', '%');

                $sheet->getStyle('A17:I17')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B050'], // Green header
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $row = 18;
                foreach ($this->education_data as $education_class) {
                    $sheet->setCellValue("A{$row}", $education_class['education']);
                    $sheet->setCellValue("B{$row}", $education_class['class_ab_count']);
                    $sheet->setCellValue("C{$row}", $education_class['class_ab_percent']);
                    $sheet->setCellValue("D{$row}", $education_class['class_c_count']);
                    $sheet->setCellValue("E{$row}", $education_class['class_c_percent']);
                    $sheet->setCellValue("F{$row}", $education_class['class_de_count']);
                    $sheet->setCellValue("G{$row}", $education_class['class_de_percent']);
                    $sheet->setCellValue("H{$row}", $education_class['total_count']);
                    $sheet->setCellValue("I{$row}", $education_class['total_percent']);

                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['size' => 10, 'name' => 'Century Gothic'],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $row++;
                }

                // Apply gray styling to the last row of the second dataset
                $lastRow = $row - 1;
                $sheet->getStyle("A{$lastRow}:I{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BFBFBF'],
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ]
                ]);

                // Apply borders to both tables
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                $sheet->getStyle("A{$firstTableStartRow}:C{$firstTableEndRow}")->applyFromArray($borderStyle);
                $sheet->getStyle("A17:I{$lastRow}")->applyFromArray($borderStyle);


                // employement status data
                $sheet->setCellValue('A33', 'EMPLOYMENT');
                $sheet->setCellValue('B33', 'CLASS AB');
                $sheet->setCellValue('C33', '%');
                $sheet->setCellValue('D33', 'CLASS C');
                $sheet->setCellValue('E33', '%');
                $sheet->setCellValue('F33', 'CLASS DE');
                $sheet->setCellValue('G33', '%');
                $sheet->setCellValue('H33', 'TOTAL');
                $sheet->setCellValue('I33', '%');

                $sheet->getStyle('A33:I33')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B050'], // Green header
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $row = 34;
                foreach ($this->employment as $employment_status) {
                    $sheet->setCellValue("A{$row}", $employment_status['employment']);
                    $sheet->setCellValue("B{$row}", $employment_status['class_ab_count']);
                    $sheet->setCellValue("C{$row}", $employment_status['class_ab_percent']);
                    $sheet->setCellValue("D{$row}", $employment_status['class_c_count']);  // Fixed column
                    $sheet->setCellValue("E{$row}", $employment_status['class_c_percent']); // Fixed column
                    $sheet->setCellValue("F{$row}", $employment_status['class_de_count']);     // Fixed column
                    $sheet->setCellValue("G{$row}", $employment_status['class_de_percent']);   // Fixed column
                    $sheet->setCellValue("H{$row}", $employment_status['total_count']);     // Fixed column
                    $sheet->setCellValue("I{$row}", $employment_status['total_percent']);

                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['size' => 10, 'name' => 'Century Gothic'],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $row++;
                }

                // Apply gray styling to the last row of the second dataset
                $lastRow = $row - 1;
                $sheet->getStyle("A{$lastRow}:I{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BFBFBF'],
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ]
                ]);

                // Apply borders to both tables
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                $sheet->getStyle("A{$firstTableStartRow}:C{$firstTableEndRow}")->applyFromArray($borderStyle);
                $sheet->getStyle("A34:I{$lastRow}")->applyFromArray($borderStyle);


                // employed occupation data
                $sheet->setCellValue('A40', 'OCCUPATION OF EMPLOYED HOME MANAGER ');
                $sheet->setCellValue('B40', 'N');
                $sheet->setCellValue('C40', '%');

                $sheet->getStyle('A40:C40')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B050'], // Green header
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $row = 41;
                foreach ($this->occupation_of_employed as $occupation) {
                    $sheet->setCellValue("A{$row}", $occupation->occupation);
                    $sheet->setCellValue("B{$row}", $occupation->count);
                    $sheet->setCellValue("C{$row}", $occupation->percentage);

                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['size' => 10, 'name' => 'Century Gothic'],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $row++;
                }
                // Apply gray styling to the last row of the second dataset
                $lastRow = $row - 1;
                $sheet->getStyle("A{$lastRow}:C{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BFBFBF'],
                    ],
                    'font' => [
                        'bold' => true,
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ]
                ]);

                // Apply borders to both tables
                $borderStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];
                $sheet->getStyle("A{$firstTableStartRow}:C{$firstTableEndRow}")->applyFromArray($borderStyle);
                $sheet->getStyle("A41:C{$lastRow}")->applyFromArray($borderStyle);
            }

        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I2');

        $sheet->getStyle('A1:I2')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
                'name' => 'Century Gothic',
                'size' => 15,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'BFBFBF'],
            ],
        ]);



        $sheet->getStyle('A4:F4')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $styles = [];

        foreach (range("A", "C") as $column) {
            $styles["{$column}4"] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '00B050'],
                ],
                'font' => [
                    'bold' => true,
                    'name' => 'Century Gothic',
                    'size' => 11,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
        }
        $highestRow = $sheet->getHighestRow();

        // Apply styles dynamically from row 4 to the last row
        for ($row = 4; $row <= $highestRow; $row++) {
            foreach (range("A", "C") as $column) {
                $sheet->getStyle("{$column}{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'font' => [
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
        }

        return $styles;
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle->applyFromArray([
            'font' => [
                'name' => 'Century Gothic',
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    public function columnFormats(): array
    {
        return [
            // "C" =>  '0%',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
        ];
    }
}
