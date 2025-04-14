<?php

namespace App\Exports\TrafficCounts;

use App\Exports\TrafficCounts\FootCountAverageExport;
use App\Exports\TrafficCounts\FootCountExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrafficFootCountExport implements WithMultipleSheets
{
    protected $target_locations;

    public function __construct($target_locations)
    {
        $this->target_locations = $target_locations;
    }


    public function sheets(): array
    {
        return [
            new FootCountExport($this->target_locations),
            new FootCountAverageExport($this->target_locations),
        ];
    }
}
