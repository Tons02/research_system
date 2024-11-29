<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UnitFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'unit_code',
        'unit_name'
    ];
}
