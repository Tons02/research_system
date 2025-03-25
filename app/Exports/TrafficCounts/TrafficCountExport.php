<?php

namespace App\Exports\TrafficCounts;

use App\Exports\TrafficCounts\VehicleCountAverageExport;
use App\Exports\TrafficCounts\VehicleCountExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrafficCountExport implements WithMultipleSheets
{
    protected $target_locations;

    public function __construct($target_locations)
    {
        $this->target_locations = $target_locations;
    }

    public function sheets(): array
    {
        return [
            new VehicleCountExport($this->target_locations),
            new VehicleCountAverageExport($this->target_locations),
        ];
    }
}
