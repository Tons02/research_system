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
        "username",
        "created_at",
    ];
}
