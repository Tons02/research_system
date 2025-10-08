<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class FootCountFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        'date',
        'time',
        'time_period',
        'total_male',
        'total_female',
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

    public function date($date)
    {
        if (!empty($date)) {
            $this->builder->whereDate('date', $date);
        }

        return $this;
    }
}
