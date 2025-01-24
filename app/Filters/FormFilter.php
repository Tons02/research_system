<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class FormFilter extends QueryFilters
{
    protected array $columnSearch = [
        "title",
        "description",
    ];
}
