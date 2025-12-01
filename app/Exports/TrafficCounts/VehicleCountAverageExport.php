<?php

namespace App\Exports\TrafficCounts;

use App\Models\VehicleCount;
use Carbon\Carbon;
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

class VehicleCountAverageExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithDefaultStyles, WithColumnWidths, WithColumnFormatting, WithEvents, WithCharts
{
    protected $target_location_id, $vehicleCounts, $surveyor_id, $start_date, $end_date;
    protected ?Chart $chart = null;

    private const SHEET_TITLE = 'Vehicle Count Average';

    // Vehicle type mappings for calculation
    private const VEHICLE_TYPES = [
        'private_car',
        'truck',
        'jeepney',
        'bus',
        'motorcycle',
        'tricycle',
        'bicycle',
        'e_bike'
    ];

    public function __construct($target_location_id, $surveyor_id = null, $start_date = null, $end_date = null)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;

        // Fetch vehicle counts and filter by location
        $data = DB::table('vehicle_counts')
            ->join('target_locations', 'vehicle_counts.target_location_id', '=', 'target_locations.id')
            ->where('vehicle_counts.target_location_id', $this->target_location_id)
            ->whereNull('vehicle_counts.deleted_at')
            ->when($this->surveyor_id !== null, function ($query) {
                $query->where('vehicle_counts.surveyor_id', $this->surveyor_id);
            })
            ->when($this->start_date && $this->end_date, function ($query) {
                $query->whereBetween('vehicle_counts.date', [$this->start_date, $this->end_date]);
            })
            ->when($this->start_date && !$this->end_date, function ($query) {
                $query->whereDate('vehicle_counts.date', '>=', $this->start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) {
                $query->whereDate('vehicle_counts.date', '<=', $this->end_date);
            })
            ->orderBy('date', 'asc')
            ->get();

        // Group the records by date
        $groupedByDate = $data->groupBy('date');

        // Initialize the result array
        $result = [];

        foreach ($groupedByDate as $date => $records) {
            $amTotal = $pmTotal = 0;
            $totalGrandTotal = 0;

            // Loop through each record for the current date
            foreach ($records as $record) {
                // Calculate grand total for this record by summing all vehicle types for both directions
                $recordTotal = $this->calculateRecordTotal($record);

                // Add the grand total to the respective period (AM or PM)
                if ($record->time_period === 'AM') {
                    $amTotal += $recordTotal;
                } elseif ($record->time_period === 'PM') {
                    $pmTotal += $recordTotal;
                }

                // Sum the grand totals for both AM and PM combined
                $totalGrandTotal += $recordTotal;
            }

            // Calculate the percentage for AM and PM
            $amPercentage = $totalGrandTotal > 0 ? ($amTotal / $totalGrandTotal) : 0;
            $pmPercentage = $totalGrandTotal > 0 ? ($pmTotal / $totalGrandTotal) : 0;

            // Store the result for this date
            $result[] = [
                'target_location' => trim(implode(', ', array_filter([
                    $records[0]->province ?? null,
                    $records[0]->city_municipality ?? null,
                    $records[0]->sub_municipality ?? null,
                    $records[0]->barangay ?? null
                ]))),
                'date' => $date,
                'am_total' => $amTotal,
                'pm_total' => $pmTotal,
                'grand_total' => $totalGrandTotal,
                'am_percentage' => round($amPercentage, 2),
                'pm_percentage' => round($pmPercentage, 2),
            ];
        }

        // Assign the result to the vehicleCounts property
        $this->vehicleCounts = $result;
    }

    /**
     * Calculate the total count for a single record by summing all vehicle types
     */
    private function calculateRecordTotal($record): int
    {
        $total = 0;

        foreach (self::VEHICLE_TYPES as $vehicleType) {
            $leftField = "total_left_{$vehicleType}";
            $rightField = "total_right_{$vehicleType}";

            $total += ($record->$leftField ?? 0) + ($record->$rightField ?? 0);
        }

        return $total;
    }

    public function collection()
    {
        return collect($this->vehicleCounts);
    }

    public function title(): string
    {
        return self::SHEET_TITLE;
    }

    public function headings(): array
    {
        return [
            ["VEHICULAR COUNT AVERAGE"],
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

    public function map($vehicle_count): array
    {
        return [
            $vehicle_count['date'],
            strtoupper(date("l", strtotime($vehicle_count['date']))),
            $vehicle_count['grand_total'],
            $vehicle_count['am_percentage'],
            $vehicle_count['pm_percentage'],
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

                // Add average calculation for total column
                $columnsToSum = ["C"];
                foreach ($columnsToSum as $column) {
                    $sheet->setCellValue(
                        "{$column}{$lastRow}",
                        "=ROUND(AVERAGE({$column}3:{$column}" . ($lastRow - 1) . "), 0)"
                    );

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

                // Add "AVERAGE" label
                $columnsToSum = ["B"];
                foreach ($columnsToSum as $column) {
                    $sheet->setCellValue("{$column}{$lastRow}", 'AVERAGE');

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
                if (!empty($this->vehicleCounts)) {
                    $dataCount = count($this->vehicleCounts);
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
                        range(0, 1), // two series (AM and PM)
                        $seriesLabels,
                        [$categories], // categories (days)
                        [$amValues, $pmValues] // AM and PM percentages
                    );

                    // Make it horizontal
                    $series->setPlotDirection(DataSeries::DIRECTION_BAR);
                    $plotArea = new PlotArea(null, [$series]);
                    $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
                    $title = new Title('Daily AM/PM Vehicle Distribution');

                    $chart = new Chart(
                        'vehicleCountAverageChart',
                        $title,
                        $legend,
                        $plotArea,
                        true,
                        0,
                        null,
                        null
                    );

                    // Position the chart to the right of the table
                    $chart->setTopLeftPosition('G4');
                    $chart->setBottomRightPosition('P21');

                    // Keep reference for WithCharts
                    $this->chart = $chart;

                    // Add chart to sheet
                    $sheet->addChart($chart);
                }

                // Build dynamic date range text
                $start = $this->start_date ? Carbon::parse($this->start_date)->format('F d, Y') : null;
                $end   = $this->end_date   ? Carbon::parse($this->end_date)->format('F d, Y') : null;

                if ($start && $end) {
                    $rangeText = "DATE FILTER: {$start} to {$end}";
                } elseif ($start && !$end) {
                    $rangeText = "DATE FILTER: From {$start}";
                } elseif (!$start && $end) {
                    $rangeText = "DATE FILTER: Up to {$end}";
                } else {
                    $rangeText = "DATE FILTER: ALL DATES";
                }

                // ===== Add Date Range Label Above Chart =====
                // Make sure text is created before chart OR after chart to make visible
                $cell = 'F1:I2';

                $sheet->mergeCells($cell);

                $sheet->setCellValueExplicit(
                    'F1',
                    $rangeText,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );

                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFC000']
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'],
                        'name' => 'Century Gothic',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // ensure columns are visible
                foreach (['F', 'G', 'H', 'I'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
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
                'vertical' => Alignment::VERTICAL_CENTER,
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
                'vertical' => Alignment::VERTICAL_CENTER,
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
            "D" => '0.00%',
            "E" => '0.00%',
        ];
    }
}
