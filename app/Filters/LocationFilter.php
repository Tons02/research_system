<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class LocationFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'location_code',
        'location_name'
    ];
}
