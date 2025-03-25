<?php

namespace App\Exports\TrafficCounts;

use App\Models\VehicleCount;
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
    protected $target_locations, $vehicleCounts;

    public function __construct($target_locations)
    {
        $this->target_locations = $target_locations;


        $this->vehicleCounts = VehicleCount::select(
            'date',
            DB::raw('SUM(CASE WHEN time_period = "AM" THEN grand_total ELSE 0 END) as total_am'),
            DB::raw('SUM(CASE WHEN time_period = "PM" THEN grand_total ELSE 0 END) as total_pm'),
            DB::raw('SUM(grand_total) as grand_total')
        )
        ->groupBy('date')
        ->get()
        ->map(function ($item) {
            $item->am_percentage = $item->grand_total ? round(($item->total_am / $item->grand_total), 2) : 0;
            $item->pm_percentage = $item->grand_total ? round(($item->total_pm / $item->grand_total), 2) : 0;
            return $item;
        });

        // Fetch all target locations in one query and map them
        $targetLocationMap = VehicleCount::with('target_locations')
            ->whereIn('date', $this->vehicleCounts->pluck('date'))
            ->get()
            ->groupBy('date');

        // Assign target locations efficiently
        foreach ($this->vehicleCounts as $count) {
            $count->target_locations = $targetLocationMap[$count->date] ?? [];
        }
    }

    public function collection()
    {
        return collect($this->vehicleCounts);
    }


    public function title(): string
    {
        return 'Vehicle Average';
    }

    public function headings(): array
    {
        $firstRecord = $this->collection()->first();


        $firstLocation = $firstRecord->target_locations[0] ?? null;

        $secondLocation = $firstLocation->target_locations[0];

        $locationParts = [
            $secondLocation['province'] ?? null,
            $secondLocation['city_municipality'] ?? null,
            $secondLocation['sub_municipality'] ?? null,
            $secondLocation['barangay'] ?? null,
        ];

        $formattedLocation = implode(', ', array_filter($locationParts ?? null));


        return [
            ["VEHICULAR AVERAGE ON {$formattedLocation}"],
            [],
            [
               'DATE', 'DAY', 'TOTAL', 'AM', 'PM'
            ]
        ];
    }

    public function map($vehicle_count): array
    {
        return [
            $vehicle_count->date,
            strtoupper(date("l", strtotime($vehicle_count->date))),
            $vehicle_count->grand_total,
            $vehicle_count->am_percentage,
            $vehicle_count->pm_percentage,
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
                'size' => 12,
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
                    'size' => 10,
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
                $sheet->getStyle("{$column}{$row}")->applyFromArray([
                    'font' => [
                        'name' => 'Century Gothic',
                        'size' => 9,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT, // Left-align text
                        'vertical' => Alignment::VERTICAL_CENTER, // Center vertically
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
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
                    $sheet->setCellValue("{$column}{$lastRow}",'AVERAGE');

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
            }
        ];
    }

    public function charts()
    {
        // Count how many rows of data exist
        $rowCount = $this->vehicleCounts->count();

        // Define dynamic range for categories (DAYS)
        $categories = [new DataSeriesValues('String', "'Vehicle Average'!\$B\$4:\$B\$" . ($rowCount + 4), null, $rowCount)];

        // Define dynamic range for values (TOTALS)
        $values = [new DataSeriesValues('Number', "'Vehicle Average'!\$C\$4:\$C\$" . ($rowCount + 4), null, $rowCount, [], 'Average')];

        // Create the data series
        $series = new DataSeries(
            DataSeries::TYPE_BARCHART_3D,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            [],
            $categories,
            $values
        );

        // Create plot area and chart
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend();
        $chart = new Chart('chart1', new Title('Vehicular Average by Day'), $legend, $plotArea);

         // Customize chart dimensions (width: 800px, height: 400px)
        $chart->setTopLeftPosition('G2');
        $chart->setBottomRightPosition('O20');

        return $chart;
    }
}
