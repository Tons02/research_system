<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class RoleFilter extends QueryFilters
{
    protected array $columnSearch = [
        "name",
        "access_permission",
    ];
}
