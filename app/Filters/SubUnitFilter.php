<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class SubUnitFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'sub_unit_code',
        'sub_unit_name'
    ];

    protected array $relationSearch = [
        'unit' => ['unit_code', 'unit_name']
    ];

    public function sync_id($sync_id)
    {
        if ($sync_id !== null) {
            $this->builder->where('sync_id', $sync_id);
        }
        return $this;
    }
}
