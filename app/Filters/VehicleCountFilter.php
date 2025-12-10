<?php

namespace App\Filters;

use Carbon\Carbon;
use Essa\APIToolKit\Filters\QueryFilters;

class VehicleCountFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        'id',
        'date',
        'time_period',
        'total_left',
        'total_right',
        'grand_total',
    ];


    protected array $relationSearch = [
        'surveyor' => ['first_name', 'middle_name', 'last_name'],
    ];

    public function surveyor_id($surveyor_id)
    {
        if ($surveyor_id !== null) {
            $this->builder->where('surveyor_id', $surveyor_id);
        }
        return $this;
    }

    public function target_location_id($target_location_id)
    {
        if ($target_location_id !== null) {
            $this->builder->where('target_location_id', $target_location_id);
        }
        return $this;
    }

    public function start_date($start_date)
    {
        if ($start_date !== null) {

            // Ensure accurate date parsing
            $start_date = Carbon::parse($start_date)->startOfDay();

            $this->builder->whereDate('date', '>=', $start_date);
        }

        return $this;
    }

    public function end_date($end_date)
    {
        if ($end_date !== null) {

            // Ensure accurate date parsing
            $end_date = Carbon::parse($end_date)->startOfDay();

            $this->builder->whereDate('date', '<=', $end_date);
        }

        return $this;
    }
}
