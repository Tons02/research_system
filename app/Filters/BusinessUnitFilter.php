<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BusinessUnitFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'business_unit_code',
        'business_unit_name'
    ];

    protected array $relationSearch = [
        'company' => ['company_name', 'company_code']
    ];

    public function company_id($company_id)
    {
        $this->builder->whereHas('company', function ($query) use ($company_id) {
            $query->where('sync_id', $company_id);
        });
    }

}
