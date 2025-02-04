<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class TargetLocationFilter extends QueryFilters
{
    protected array $columnSearch = [
        'id',
        'target_location',
        'form_id',
    ];
}
