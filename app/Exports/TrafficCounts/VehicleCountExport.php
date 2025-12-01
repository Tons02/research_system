<?php

namespace App\Exports\TrafficCounts;

use App\Models\VehicleCount;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
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

class VehicleCountExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithMapping,
    WithDefaultStyles,
    WithColumnWidths,
    WithCharts,
    WithEvents
{
    protected $target_location_id;
    protected $surveyor_id;
    protected $start_date;
    protected $end_date;
    protected $data;
    protected ?Chart $chart = null;

    private const SHEET_TITLE = 'Vehicle Count Summary';

    public function __construct($target_location_id, $surveyor_id, $start_date = null, $end_date = null)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function collection()
    {
        $this->data = VehicleCount::selectRaw("
            vehicle_counts.date,
            vehicle_counts.time_period,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    COALESCE(surveyors.first_name, ''), ' ', COALESCE(surveyors.last_name, '')
                ) SEPARATOR ', '
            ) as surveyors,
            SUM(vehicle_counts.total_left) as total_left,
            SUM(vehicle_counts.total_right) as total_right,
            SUM(vehicle_counts.grand_total) as grand_total
        ")
            ->leftJoin('users as surveyors', 'vehicle_counts.surveyor_id', '=', 'surveyors.id')
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
            ->groupBy('vehicle_counts.date', 'vehicle_counts.time_period')
            ->orderBy('vehicle_counts.date', 'asc')
            ->orderByRaw("FIELD(vehicle_counts.time_period, 'AM', 'PM')")
            ->get();

        return $this->data;
    }

    public function title(): string
    {
        return self::SHEET_TITLE;
    }

    public function headings(): array
    {
        return [
            ["VEHICLE COUNT SUMMARY"],
            [],
            [
                'DATE',
                'TIME PERIOD',
                'TOTAL LEFT',
                'TOTAL RIGHT',
                'GRAND TOTAL',
                'RESEARCHER(S)',
            ]
        ];
    }

    public function map($vehicle_count): array
    {
        return [
            $vehicle_count->date,
            $vehicle_count->time_period,
            $vehicle_count->total_left,
            $vehicle_count->total_right,
            $vehicle_count->grand_total,
            $vehicle_count->surveyors ?? 'N/A',
        ];
    }

    public function charts()
    {
        return $this->chart ? [$this->chart] : [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (!$this->data || $this->data->isEmpty()) {
                    return;
                }

                $sheet = $event->sheet->getDelegate();

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
                $cell = 'G1:J2';

                $sheet->mergeCells($cell);

                $sheet->setCellValueExplicit(
                    'G1',
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
                foreach (['G', 'H', 'I', 'J'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }

                // Layout:
                // Row 1-2: Title (merged)
                // Row 3:   Headers
                // Row 4+:  Data
                $startDataRow = 4;
                $dataCount    = $this->data->count();
                $endDataRow   = $startDataRow + $dataCount - 1;

                $sheetTitleQuoted = "'" . self::SHEET_TITLE . "'";

                // ===== Multi-level Category Axis (Time Period + Date) =====
                // Primary level: Time Period (AM/PM)
                $categoryLevel1 = new DataSeriesValues(
                    DataSeriesValues::DATASERIES_TYPE_STRING,
                    "{$sheetTitleQuoted}!\$A\${$startDataRow}:\$B\${$endDataRow}",
                    null,
                    $dataCount
                );

                // Secondary level: Date
                $categoryLevel2 = new DataSeriesValues(
                    DataSeriesValues::DATASERIES_TYPE_STRING,
                    "{$sheetTitleQuoted}!\$A\${$startDataRow}:\$A\${$endDataRow}",
                    null,
                    $dataCount
                );

                // ===== Two Series: TOTAL LEFT and TOTAL RIGHT =====

                // Series 1: TOTAL LEFT (Column C)
                $leftValues = new DataSeriesValues(
                    DataSeriesValues::DATASERIES_TYPE_NUMBER,
                    "{$sheetTitleQuoted}!\$C\${$startDataRow}:\$C\${$endDataRow}",
                    null,
                    $dataCount
                );

                // Series 2: TOTAL RIGHT (Column D)
                $rightValues = new DataSeriesValues(
                    DataSeriesValues::DATASERIES_TYPE_NUMBER,
                    "{$sheetTitleQuoted}!\$D\${$startDataRow}:\$D\${$endDataRow}",
                    null,
                    $dataCount
                );

                // Series labels from headers C3 and D3
                $seriesLabels = [
                    new DataSeriesValues(
                        DataSeriesValues::DATASERIES_TYPE_STRING,
                        "{$sheetTitleQuoted}!\$C\$3",
                        null,
                        1
                    ),
                    new DataSeriesValues(
                        DataSeriesValues::DATASERIES_TYPE_STRING,
                        "{$sheetTitleQuoted}!\$D\$3",
                        null,
                        1
                    )
                ];

                // Build series with two data series (TOTAL LEFT and TOTAL RIGHT)
                $series = new DataSeries(
                    DataSeries::TYPE_BARCHART,
                    DataSeries::GROUPING_CLUSTERED,
                    range(0, 1),               // two series (0 and 1)
                    $seriesLabels,
                    [$categoryLevel1, $categoryLevel2], // multi-level: Time Period first, then Date
                    [$leftValues, $rightValues]         // both left and right values
                );
                $series->setPlotDirection(DataSeries::DIRECTION_COL);

                // Enable multi-level category axis
                if (method_exists($series, 'setPlotCategoryAxisType')) {
                    $series->setPlotCategoryAxisType(DataSeries::PLOT_CATEGORY_AXIS_TYPE_MULTI_LEVEL);
                }

                $plotArea = new PlotArea(null, [$series]);
                $legend   = new Legend(Legend::POSITION_BOTTOM, null, false);
                $title    = new Title('Vehicle Count Summary');

                $chart = new Chart(
                    'vehicleCountChart',
                    $title,
                    $legend,
                    $plotArea,
                    true,
                    0,
                    null,
                    null
                );

                // Place the chart to the right of the table
                $chart->setTopLeftPosition('H4');
                $chart->setBottomRightPosition('Q26');

                // Keep a reference so WithCharts will include it
                $this->chart = $chart;

                // And add it to the sheet
                $sheet->addChart($chart);
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title styling (A1:F2)
        $sheet->mergeCells('A1:F2');
        $sheet->getStyle('A1:F2')->applyFromArray([
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

        // Header row styling
        $sheet->getStyle('A3:F3')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $styles = [];
        foreach (range("A", "F") as $column) {
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
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
        }

        // Zebra + borders on data rows
        $highestRow = $sheet->getHighestRow();
        for ($row = 4; $row <= $highestRow; $row++) {
            foreach (range("A", "F") as $column) {
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
                        'startColor' => ['rgb' => $fillColor],
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
            'C' => 17,
            'D' => 17,
            'E' => 17,
            'F' => 25,
        ];
    }
}
