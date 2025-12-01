<?php

namespace App\Exports\TrafficCounts;

use App\Models\FootCount;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
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

class FootCountExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping, WithDefaultStyles, WithColumnWidths, WithEvents
{
    protected $target_location_id, $surveyor_id, $start_date, $end_date;

    public function __construct($target_location_id, $surveyor_id, $start_date = null, $end_date = null)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function collection()
    {
        return FootCount::with('target_locations')
            ->whereHas('target_locations', function ($query) {
                $query->where('target_location_id', $this->target_location_id)
                    ->when($this->surveyor_id !== null, function ($query) {
                        $query->where('foot_counts.surveyor_id', $this->surveyor_id);
                    });
            })
            ->when($this->start_date && $this->end_date, function ($query) {
                $query->whereBetween('date', [$this->start_date, $this->end_date]);
            })
            ->when($this->start_date && !$this->end_date, function ($query) {
                $query->whereDate('date', '>=', $this->start_date);
            })
            ->when(!$this->start_date && $this->end_date, function ($query) {
                $query->whereDate('date', '<=', $this->end_date);
            })
            ->whereNull('deleted_at')
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
        return 'Foot Count Breakdown';
    }

    public function headings(): array
    {

        return [
            ["FOOT COUNT BREAKDOWN"],
            [],
            [
                'ID',
                'DATE',
                'TIME RANGE',
                'TIME PERIOD',
                'TOTAL LEFT MALE',
                'TOTAL RIGHT MALE',
                'TOTAL LEFT FEMALE',
                'TOTAL RIGHT FEMALE',
                'TOTAL MALE',
                'TOTAL FEMALE',
                'GRAND TOTAL',
                'SURVEYOR',
                'CREATED AT',
                'SYNC AT',
            ]
        ];
    }

    public function map($foot_count): array
    {
        // Calculate totals in case they aren't stored
        $totalMale = $foot_count->total_male ??
            (($foot_count->total_left_male ?? 0) + ($foot_count->total_right_male ?? 0));

        $totalFemale = $foot_count->total_female ??
            (($foot_count->total_left_female ?? 0) + ($foot_count->total_right_female ?? 0));

        $grandTotal = $foot_count->grand_total ?? ($totalMale + $totalFemale);

        // Get surveyor name (with fallbacks if deleted)
        $surveyorName = $foot_count->surveyor
            ? trim(($foot_count->surveyor->first_name ?? '') . ' ' . ($foot_count->surveyor->last_name ?? ''))
            : 'N/A';

        return [
            $foot_count->id,
            $foot_count->date,
            $foot_count->time_range,
            $foot_count->time_period,
            $foot_count->total_left_male,
            $foot_count->total_right_male,
            $foot_count->total_left_female,
            $foot_count->total_right_female,
            $totalMale,
            $totalFemale,
            $grandTotal,
            $surveyorName,
            Carbon::parse($foot_count->created_at)->format('M d, Y h:i A'),
            Carbon::parse($foot_count->sync_at)->format('M d, Y h:i A'),
        ];
    }


    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:N2');

        $sheet->getStyle('A1:L2')->applyFromArray([
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

        $sheet->getStyle('A3:N3')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $styles = [];

        foreach (range("A", "N") as $column) {
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
            foreach (range("A", "N") as $column) {

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


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
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
                $cell = 'O1:R2';

                $sheet->mergeCells($cell);

                $sheet->setCellValueExplicit(
                    'O1',
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
                foreach (['O', 'P', 'Q', 'R'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
            }
        ];
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
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 25,
            'I' => 15,
            'J' => 15,
            'K' => 20,
            'L' => 25,
            'M' => 25,
            'N' => 25,
        ];
    }
}
