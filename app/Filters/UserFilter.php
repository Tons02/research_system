<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UserFilter extends QueryFilters
{
    protected array $columnSearch = [
        "id_prefix",

        "id_no",
        "first_name",
        "middle_name",
        "last_name",
        "mobile_number",
        "username"
    ];

    protected array $relationSearch = [
        'company' => ['company_name'],
        'business_unit' => ['business_unit_name'],
        'department' => ['department_name'],
        'unit' => ['unit_name'],
        'sub_unit' => ['sub_unit_name'],
        'location' => ['location_name'],
        'role' => ['name']
    ];
}
