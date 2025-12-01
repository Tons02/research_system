<?php

namespace App\Exports\TrafficCounts;

use App\Exports\TrafficCounts\VehicleCountAverageExport;
use App\Exports\TrafficCounts\VehicleCountExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrafficCountExport implements WithMultipleSheets
{
    protected $target_location_id, $surveyor_id, $start_date, $end_date;

    public function __construct($target_location_id, $surveyor_id, $start_date = null, $end_date = null)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function sheets(): array
    {
        return [
            new VehicleCountExport($this->target_location_id, $this->surveyor_id, $this->start_date, $this->end_date),
            new VehicleCountAverageExport($this->target_location_id, $this->surveyor_id, $this->start_date, $this->end_date),
            new VehicleBreakdownExport($this->target_location_id, $this->surveyor_id, $this->start_date, $this->end_date),
        ];
    }
}
