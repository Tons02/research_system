<?php

namespace App\Exports\SurveyReports;

use App\Exports\TrafficCounts\FootCountAverageExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OverAllReport implements WithMultipleSheets
{
    protected $target_location_id, $surveyor_id, $from_date, $to_date, $status;

    public function __construct($target_location_id, $surveyor_id, $from_date, $to_date, $status)
    {
        $this->target_location_id = $target_location_id;
        $this->surveyor_id = $surveyor_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->status = $status;
    }

    public function sheets(): array
    {
        return [
            new DemographicExport($this->target_location_id, $this->surveyor_id, $this->from_date, $this->to_date, $this->status),
            new ResponseClassAB($this->target_location_id, $this->surveyor_id, $this->from_date, $this->to_date, $this->status),
            new ResponseClassC($this->target_location_id, $this->surveyor_id, $this->from_date, $this->to_date, $this->status),
            new ResponseClassDE($this->target_location_id, $this->surveyor_id, $this->from_date, $this->to_date, $this->status),
        ];
    }
}
