<?php

namespace App\Exports\SurveyReports;

use App\Exports\TrafficCounts\FootCountAverageExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OverAllReport implements WithMultipleSheets
{
    protected $target_location_id;

    public function __construct($target_location_id)
    {
        $this->target_location_id = $target_location_id;
    }


    public function sheets(): array
    {
        return [
            new DemographicExport($this->target_location_id),
            new ResponseClassAB($this->target_location_id),
            new ResponseClassC($this->target_location_id),
            new ResponseClassDE($this->target_location_id),
        ];
    }
}
