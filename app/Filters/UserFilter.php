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
        "contact_details",
        "username",
        "created_at", 
    ];
}
