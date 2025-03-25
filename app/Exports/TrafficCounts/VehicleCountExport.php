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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehicleCountExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithDefaultStyles, WithColumnWidths
{
    protected $target_locations;

    public function __construct($target_locations)
    {
        $this->target_locations = $target_locations;
    }

    public function collection()
    {
        return VehicleCount::with('target_locations')
        ->whereHas('target_locations', function ($query) {
            $query->where('target_location_id', $this->target_locations);
        })
        ->orderBy('id', 'desc')
        ->get();
    }

    public function title(): string
    {
        return 'Vehicle Count';
    }

    public function headings(): array
    {
        $firstRecord = $this->collection()->first();

        if ($firstRecord && $firstRecord->target_locations->isNotEmpty()) {
            $location = $firstRecord->target_locations->first();

            $locationParts = [
                $location->province ?? null,
                $location->city_municipality ?? null,
                $location->sub_municipality ?? null,
                $location->barangay ?? null,
                // $location->street ?? null,
            ];

            $formattedLocation = implode(', ', array_filter($locationParts));
        } else {
            $formattedLocation = 'Unknown Location';
        }

        return [
            ["VEHICULAR COUNT ON {$formattedLocation}"],
            [],
            [
               'ID', 'DATE', 'TIME', 'LEFT', 'RIGHT', 'GRAND TOTAL'
            ]
        ];
    }

    public function map($vehicle_count): array
    {
        return [
            $vehicle_count->id,
            $vehicle_count->date,
            date("g:i A", strtotime($vehicle_count->time)),
            $vehicle_count->total_left,
            $vehicle_count->total_right,
            $vehicle_count->grand_total,
        ];
    }

    public function styles(Worksheet $sheet)
    {
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
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
            ];
        }
        $highestRow = $sheet->getHighestRow();

        // Apply styles dynamically from row 4 to the last row
        for ($row = 4; $row <= $highestRow; $row++) {
            foreach (range("A", "F") as $column) {
                $sheet->getStyle("{$column}{$row}")->applyFromArray([
                    'font' => [
                        'name' => 'Century Gothic',
                        'size' => 10,
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
            'A' => 10,
            'B' => 20,
            'C' => 20,
            'D' => 15,
            'E' => 15,
            'F' => 15,
        ];
    }

}
