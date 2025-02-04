<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UnitFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'unit_code',
        'unit_name'
    ];

    public function sync_id($sync_id)
    {
        if ($sync_id !== null) {
            $this->builder->where('sync_id', $sync_id);
        }
        return $this;
    }
}
