<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class VehicleCountFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        'date',
        'time',
        'time_period',
        'total_left',
        'total_right',
    ];


    protected array $relationSearch = [
        'surveyor' => ['first_name','middle_name', 'last_name'],
    ];

    public function surveyor_id($surveyor_id)
    {
        if ($surveyor_id !== null) {
            $this->builder->where('surveyor_id', $surveyor_id);
        }
        return $this;
    }
}
