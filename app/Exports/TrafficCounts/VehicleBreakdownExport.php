<?php

namespace App\Exports\TrafficCounts;

use App\Models\VehicleCount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehicleBreakdownExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithDefaultStyles, WithColumnWidths
{
    protected $target_location_id, $surveyor_id;

    // Vehicle types for calculation
    private const VEHICLE_TYPES = [
        'private_car' => 'Private Car',
        'truck' => 'Truck',
        'jeepney' => 'Jeepney',
        'bus' => 'Bus',
        'tricycle' => 'Tricycle',
        'bicycle' => 'Bicycle',
        'e_bike' => 'E-Bike'
    ];

    public function __construct($target_location_id, $surveyor_id = null)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
    }

    public function collection()
    {
        return VehicleCount::with('target_locations')
            ->whereHas('target_locations', function ($query) {
                $query->where('target_location_id', $this->target_location_id)
                    ->when($this->surveyor_id !== null, function ($query) {
                        $query->where('vehicle_counts.surveyor_id', $this->surveyor_id);
                    });
            })
            ->orderBy('date', 'asc')
            ->orderByRaw("
                FIELD(time_period, 'AM', 'PM')
            ")
            ->orderByRaw("
                STR_TO_DATE(
                    LPAD(SUBSTRING_INDEX(time_range, ' - ', 1), 5, '0'),
                    '%h:%i'
                ) ASC
            ")
            ->get();
    }

    public function title(): string
    {
        return 'Vehicle Count Breakdown';
    }

    public function headings(): array
    {
        return [
            ["VEHICLE COUNT BREAKDOWN"],
            [],
            [
                'ID',
                'DATE',
                'TIME RANGE',
                'TIME PERIOD',
                'LEFT PRIVATE CAR',
                'RIGHT PRIVATE CAR',
                'LEFT TRUCK',
                'RIGHT TRUCK',
                'LEFT JEEPNEY',
                'RIGHT JEEPNEY',
                'LEFT BUS',
                'RIGHT BUS',
                'LEFT TRICYCLE',
                'RIGHT TRICYCLE',
                'LEFT BICYCLE',
                'RIGHT BICYCLE',
                'LEFT E-BIKE',
                'RIGHT E-BIKE',
                'TOTAL LEFT',
                'TOTAL RIGHT',
                'GRAND TOTAL',
                'SURVEYOR',
            ]
        ];
    }

    public function map($vehicle_count): array
    {
        // Calculate totals for left and right directions
        $totalLeft = $this->calculateDirectionTotal($vehicle_count, 'left');
        $totalRight = $this->calculateDirectionTotal($vehicle_count, 'right');
        $grandTotal = $totalLeft + $totalRight;

        // Get surveyor name (with fallbacks if deleted)
        $surveyorName = $vehicle_count->surveyor
            ? trim(($vehicle_count->surveyor->first_name ?? '') . ' ' . ($vehicle_count->surveyor->last_name ?? ''))
            : 'N/A';

        return [
            $vehicle_count->id,
            $vehicle_count->date,
            $vehicle_count->time_range,
            $vehicle_count->time_period,
            $vehicle_count->total_left_private_car ?? 0,
            $vehicle_count->total_right_private_car ?? 0,
            $vehicle_count->total_left_truck ?? 0,
            $vehicle_count->total_right_truck ?? 0,
            $vehicle_count->total_left_jeepney ?? 0,
            $vehicle_count->total_right_jeepney ?? 0,
            $vehicle_count->total_left_bus ?? 0,
            $vehicle_count->total_right_bus ?? 0,
            $vehicle_count->total_left_tricycle ?? 0,
            $vehicle_count->total_right_tricycle ?? 0,
            $vehicle_count->total_left_bicycle ?? 0,
            $vehicle_count->total_right_bicycle ?? 0,
            $vehicle_count->total_left_e_bike ?? 0,
            $vehicle_count->total_right_e_bike ?? 0,
            $totalLeft,
            $totalRight,
            $grandTotal,
            $surveyorName,
        ];
    }

    /**
     * Calculate total for a specific direction (left or right)
     */
    private function calculateDirectionTotal($vehicle_count, $direction): int
    {
        $total = 0;

        foreach (array_keys(self::VEHICLE_TYPES) as $vehicleType) {
            $field = "total_{$direction}_{$vehicleType}";
            $total += $vehicle_count->$field ?? 0;
        }

        return $total;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:V2');

        $sheet->getStyle('A1:V2')->applyFromArray([
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

        $sheet->getStyle('A3:V3')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $styles = [];

        foreach (range("A", "V") as $column) {
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

        $highestRow = $sheet->getHighestRow();

        // Apply styles dynamically from row 4 to the last row
        for ($row = 4; $row <= $highestRow; $row++) {
            foreach (range("A", "V") as $column) {
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
            'A' => 8,   // ID
            'B' => 15,  // DATE
            'C' => 18,  // TIME RANGE
            'D' => 15,  // TIME PERIOD
            'E' => 18,  // LEFT PRIVATE CAR
            'F' => 20,  // RIGHT PRIVATE CAR
            'G' => 15,  // LEFT TRUCK
            'H' => 15,  // RIGHT TRUCK
            'I' => 15,  // LEFT JEEPNEY
            'J' => 15,  // RIGHT JEEPNEY
            'K' => 15,  // LEFT BUS
            'L' => 15,  // RIGHT BUS
            'M' => 15,  // LEFT TRICYCLE
            'N' => 17,  // RIGHT TRICYCLE
            'O' => 15,  // LEFT BICYCLE
            'P' => 15,  // RIGHT BICYCLE
            'Q' => 15,  // LEFT E-BIKE
            'R' => 15,  // RIGHT E-BIKE
            'S' => 12,  // TOTAL LEFT
            'T' => 13,  // TOTAL RIGHT
            'U' => 15,  // GRAND TOTAL
            'V' => 22,  // SURVEYOR
        ];
    }
}
