<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class CompanyFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'company_code',
        'company_name'
    ];
}
