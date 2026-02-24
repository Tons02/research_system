<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PendingUserFilter extends QueryFilters
{
    protected array $columnSearch = ['fname', 'lname', 'email', 'employee_id'];
}
