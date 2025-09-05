<?php

namespace App\Exports\TrafficCounts;

use App\Exports\TrafficCounts\VehicleCountAverageExport;
use App\Exports\TrafficCounts\VehicleCountExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrafficCountExport implements WithMultipleSheets
{
    protected $target_location_id, $surveyor_id;

    public function __construct($target_location_id, $surveyor_id)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
    }

    public function sheets(): array
    {
        return [
            new VehicleCountExport($this->target_location_id, $this->surveyor_id),
            new VehicleCountAverageExport($this->target_location_id, $this->surveyor_id),
            new VehicleBreakdownExport($this->target_location_id, $this->surveyor_id),
        ];
    }
}
