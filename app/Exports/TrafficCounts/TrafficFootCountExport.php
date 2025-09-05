<?php

namespace App\Exports\TrafficCounts;

use App\Exports\TrafficCounts\FootCountAverageExport;
use App\Exports\TrafficCounts\FootCountExport;
use App\Exports\TrafficCounts\FootCountSummaryExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrafficFootCountExport implements WithMultipleSheets
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
            new FootCountSummaryExport($this->target_location_id, $this->surveyor_id),
            new FootCountAverageExport($this->target_location_id, $this->surveyor_id),
            new FootCountExport($this->target_location_id, $this->surveyor_id),
        ];
    }
}
