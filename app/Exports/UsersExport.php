<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $status;

    public function __construct($status)
    {
        $this->status = $status;
    }

    public function collection()
    {

        return User::with([
            'company',
            'business_unit',
            'department',
            'unit',
            'sub_unit',
            'location',
            'role'
        ])->when($this->status === "inactive", function ($query) {
            $query->onlyTrashed();
        })->get();

    }

    public function headings(): array
    {
        return [
            ["{$this->status} Locus Users"],
            [
                'ID', 'ID PREFIX', 'ID NO', 'First Name', 'Last Name',
                'Mobile Number', 'Gender', 'Company', 'Business Unit',
                'Department', 'Unit', 'Sub Unit', 'Location',
                'Username', 'Role', 'Created At', 'Status'
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Format the title (Status + " Locus Users")
                $statusText = ucfirst($this->status) . " Locus Users";
                $columnCount = 17; // Adjust based on actual columns
                $mergeRange = 'A1:' . chr(65 + $columnCount - 1) . '1';
                $event->sheet->mergeCells($mergeRange);
                $event->sheet->setCellValue('A1', $statusText);

                // Apply bold and large font to the title
                $richText = new RichText();
                $firstLetter = $richText->createTextRun(substr($statusText, 0, 1));
                $firstLetter->getFont()->setSize(20)->setBold(true);

                $remainingText = $richText->createTextRun(substr($statusText, 1));
                $remainingText->getFont()->setSize(14)->setBold(true);

                $event->sheet->getDelegate()->getCell('A1')->setValue($richText);
                $event->sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                // 🔥 **Bold and Set Font Size to 15 for Headings**
                $event->sheet->getStyle('A2:Q2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 15
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center'
                    ]
                ]);

                // **Set Font Size for User Rows (WITHOUT BOLD)**
                $rowCount = User::count() + 2; // Adjust based on headings being in row 2
                $event->sheet->getStyle("A3:Q{$rowCount}")->applyFromArray([
                    'font' => [
                        'size' => 12,  // Set size to 12
                        'name' => 'Arial' // Optional: Change font type
                    ],
                    'alignment' => [
                        'vertical' => 'center'
                    ],
                ]);
            },
        ];
    }


    public function map($user): array
    {
        return [
            $user->id,
            $user->id_prefix,
            $user->id_no,
            $user->first_name,
            $user->last_name,
            " ".$user->mobile_number,
            $user->gender,
            $user->company->company_name,
            $user->business_unit->business_unit_name,
            $user->department->department_name,
            $user->unit->unit_name,
            $user->sub_unit->sub_unit_name,
            $user->location->location_name,
            $user->username,
            $user->role->name,
            $user->created_at->format('Y-m-d H:i:s'),
            $user->deleted_at ? 'Inactive' : 'Active',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Make headings bold
        ];
    }
}
