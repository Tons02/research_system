<?php

namespace App\Exports\TrafficCounts;

use App\Models\FootCount;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FootCountAverageExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithDefaultStyles, WithColumnWidths, WithColumnFormatting, WithEvents, WithCharts
{
    protected $target_location_id, $footCounts, $surveyor_id;
    protected ?Chart $chart = null;

    private const SHEET_TITLE = 'Foot Count Average';

    public function __construct($target_location_id, $surveyor_id)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;

        // Fetch foot counts and filter by location using whereHas
        $data = DB::table('foot_counts')
            ->join('target_locations', 'foot_counts.target_location_id', '=', 'target_locations.id')
            ->where('foot_counts.target_location_id', $this->target_location_id)
            ->when($this->surveyor_id !== null, function ($query) {
                $query->where('foot_counts.surveyor_id', $this->surveyor_id);
            })
            ->orderBy('date', 'asc')
            ->get();

        // Group the records by date
        $groupedByDate = $data->groupBy('date');

        // Initialize the result array
        $result = [];

        foreach ($groupedByDate as $date => $records) {
            $totalFemale = $totalMale = 0;
            $totalGrandTotal = 0;

            // Loop through each record for the current date
            foreach ($records as $record) {
                // Add the grand total to the respective period (AM or PM)
                if ($record->time_period === 'AM') {
                    $totalFemale += $record->grand_total;
                } elseif ($record->time_period === 'PM') {
                    $totalMale += $record->grand_total;
                }

                // Sum the grand totals for both AM and PM combined
                $totalGrandTotal += $record->grand_total;
            }

            // Calculate the percentage for AM and PM
            $femalePercentage = $totalGrandTotal > 0 ? ($totalFemale / $totalGrandTotal) : 0;
            $malePercentage = $totalGrandTotal > 0 ? ($totalMale / $totalGrandTotal) : 0;

            // Store the result for this date
            $result[] = [
                'target_location' => trim(implode(', ', array_filter([
                    $records[0]->province ?? null,
                    $records[0]->city_municipality ?? null,
                    $records[0]->sub_municipality ?? null,
                    $records[0]->barangay ?? null
                ]))),
                'date' => $date,
                'total_female' => $totalFemale,
                'total_male' => $totalMale,
                'grand_total' => $totalGrandTotal,
                'female_percentage' => round($femalePercentage, 2),
                'male_percentage' => round($malePercentage, 2),
            ];
        }

        // Assign the result to the footCounts property
        $this->footCounts = $result;
    }

    public function collection()
    {
        return collect($this->footCounts);
    }

    public function title(): string
    {
        return self::SHEET_TITLE;
    }

    public function headings(): array
    {
        return [
            ["FOOT COUNT AVERAGE"],
            [],
            [
                'DATE',
                'DAY',
                'TOTAL',
                'AM',
                'PM'
            ]
        ];
    }

    public function map($foot_count): array
    {
        return [
            $foot_count['date'],
            strtoupper(date("l", strtotime($foot_count['date']))),
            $foot_count['grand_total'],
            $foot_count['female_percentage'],
            $foot_count['male_percentage'],
        ];
    }

    // Keep WithCharts so Laravel Excel includes charts in the writer
    public function charts()
    {
        return $this->chart ? [$this->chart] : [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow() + 1;

                $columnsToSum = ["C"];

                foreach ($columnsToSum as $column) {
                    // Set the formula for the average
                    $sheet->setCellValue(
                        "{$column}{$lastRow}",
                        "=ROUND(AVERAGE({$column}3:{$column}" . ($lastRow - 1) . "), 0)"
                    );

                    // Apply styling to the cell
                    $sheet->getStyle("{$column}{$lastRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'name' => 'Century Gothic',
                            'size' => 9,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }

                $columnsToSum = ["B"];

                foreach ($columnsToSum as $column) {
                    // Set the formula for the average
                    $sheet->setCellValue("{$column}{$lastRow}", 'AVERAGE');

                    // Apply styling to the cell
                    $sheet->getStyle("{$column}{$lastRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'name' => 'Century Gothic',
                            'size' => 9,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }

                // ===== CREATE HORIZONTAL BAR CHART =====
                if (!empty($this->footCounts)) {
                    $dataCount = count($this->footCounts);
                    $startDataRow = 4;
                    $endDataRow = $startDataRow + $dataCount - 1;

                    $sheetTitleQuoted = "'" . self::SHEET_TITLE . "'";

                    // Categories: Days of the week (Column B)
                    $categories = new DataSeriesValues(
                        DataSeriesValues::DATASERIES_TYPE_STRING,
                        "{$sheetTitleQuoted}!\$B\${$startDataRow}:\$B\${$endDataRow}",
                        null,
                        $dataCount
                    );

                    // Series 1: AM percentages (Column D)
                    $amValues = new DataSeriesValues(
                        DataSeriesValues::DATASERIES_TYPE_NUMBER,
                        "{$sheetTitleQuoted}!\$D\${$startDataRow}:\$D\${$endDataRow}",
                        null,
                        $dataCount
                    );

                    // Series 2: PM percentages (Column E)
                    $pmValues = new DataSeriesValues(
                        DataSeriesValues::DATASERIES_TYPE_NUMBER,
                        "{$sheetTitleQuoted}!\$E\${$startDataRow}:\$E\${$endDataRow}",
                        null,
                        $dataCount
                    );

                    // Series labels from headers D3 and E3
                    $seriesLabels = [
                        new DataSeriesValues(
                            DataSeriesValues::DATASERIES_TYPE_STRING,
                            "{$sheetTitleQuoted}!\$D\$3",
                            null,
                            1
                        ),
                        new DataSeriesValues(
                            DataSeriesValues::DATASERIES_TYPE_STRING,
                            "{$sheetTitleQuoted}!\$E\$3",
                            null,
                            1
                        )
                    ];

                    // Create horizontal bar chart
                    $series = new DataSeries(
                        DataSeries::TYPE_BARCHART,
                        DataSeries::GROUPING_CLUSTERED,
                        range(0, 1),               // two series (AM and PM)
                        $seriesLabels,
                        [$categories],             // categories (days)
                        [$amValues, $pmValues]     // AM and PM percentages
                    );

                    // Make it horizontal
                    $series->setPlotDirection(DataSeries::DIRECTION_BAR);

                    $plotArea = new PlotArea(null, [$series]);
                    $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
                    $title = new Title('Daily AM/PM Distribution');

                    $chart = new Chart(
                        'footCountAverageChart',
                        $title,
                        $legend,
                        $plotArea,
                        true,
                        0,
                        null,
                        null
                    );

                    // Position the chart to the right of the table
                    $chart->setTopLeftPosition('G3');
                    $chart->setBottomRightPosition('P20');

                    // Keep reference for WithCharts
                    $this->chart = $chart;

                    // Add chart to sheet
                    $sheet->addChart($chart);
                }
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:E2');

        $sheet->getStyle('A1:E2')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
                'name' => 'Century Gothic',
                'size' => 13,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'BFBFBF'],
            ],
        ]);

        $sheet->getStyle('A3:E3')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $styles = [];

        foreach (range("A", "E") as $column) {
            $styles["{$column}3"] = [
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
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
        }
        $highestRow = $sheet->getHighestRow();

        // Apply styles dynamically from row 4 to the last row
        for ($row = 4; $row <= $highestRow; $row++) {
            foreach (range("A", "E") as $column) {

                // Determine background color: grey for even rows, white for odd rows
                $fillColor = ($row % 2 === 0) ? 'F2F2F2' : 'FFFFFF';

                $sheet->getStyle("{$column}{$row}")->applyFromArray([
                    'font' => [
                        'name' => 'Century Gothic',
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => $fillColor,
                        ],
                    ],
                ]);
            }
        }

        return $styles;
    }

    public function defaultStyles(Style $defaultStyle)
    {
        $defaultStyle->getFont()->setName('Century Gothic')->setSize(10);

        return $defaultStyle;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }

    public function columnFormats(): array
    {
        return [
            "D" =>  '0.00%',
            "E" =>  '0.00%',
        ];
    }
}
