<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class DepartmentFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'department_code',
        'department_name'
    ];


    public function business_unit_id($business_unit_id)
    {
        $this->builder->whereHas('business_unit', function ($query) use ($business_unit_id) {
            $query->where('sync_id', $business_unit_id);
        });
    }

    public function sync_id($sync_id)
    {
        if ($sync_id !== null) {
            $this->builder->where('sync_id', $sync_id);
        }
        return $this;
    }
}
