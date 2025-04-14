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
}
