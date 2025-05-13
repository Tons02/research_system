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
}
