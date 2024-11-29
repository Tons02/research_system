<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class DepartmentFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'department_code',
        'department_name'
    ];
}
