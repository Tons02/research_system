<?php

namespace App\Exports\TrafficCounts;

use App\Models\FootCount;
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

class FootCountExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithDefaultStyles, WithColumnWidths
{
    protected $target_location_id;

    public function __construct($target_location_id)
    {
        $this->target_location_id = $target_location_id;
    }

    public function collection()
    {
        return FootCount::with('target_locations')
            ->whereHas('target_locations', function ($query) {
                $query->where('target_location_id', $this->target_location_id);
            })
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();
    }

    public function title(): string
    {
        return 'Foot Count';
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
            $formattedLocation = 'NO AVAILABLE DATA';
        }

        return [
            ["FOOT COUNT ON " . ($formattedLocation ?? 'NO AVAILABLE DATA')],
            [],
            [
                'ID',
                'DATE',
                'TIME',
                'FEMALE',
                'MALE',
                'GRAND TOTAL'
            ]
        ];
    }

    public function map($foot_count): array
    {
        return [
            $foot_count->id,
            $foot_count->date,
            date("A", strtotime($foot_count->time)),
            $foot_count->total_male,
            $foot_count->total_female,
            $foot_count->grand_total,
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
                        'borderStyle' => Border::BORDER_THIN,
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
