<?php

namespace App\Exports\TrafficCounts;

use App\Exports\TrafficCounts\FootCountAverageExport;
use App\Exports\TrafficCounts\FootCountExport;
use App\Exports\TrafficCounts\FootCountSummaryExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrafficFootCountExport implements WithMultipleSheets
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
            new FootCountSummaryExport($this->target_location_id, $this->surveyor_id, $this->start_date, $this->end_date),
            new FootCountAverageExport($this->target_location_id, $this->surveyor_id, $this->start_date, $this->end_date),
            new FootCountExport($this->target_location_id, $this->surveyor_id, $this->start_date, $this->end_date),
        ];
    }
}
