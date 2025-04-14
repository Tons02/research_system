<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class FormHistoriesFilter extends QueryFilters
{
    protected array $columnSearch = [
        "title",
        "description",
    ];

    protected array $allowedSorts = ['created_at', 'updated_at', 'title'];
}
