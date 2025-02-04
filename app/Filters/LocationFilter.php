<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class LocationFilter extends QueryFilters
{
    protected array $columnSearch = [
        'sync_id',
        'location_code',
        'location_name'
    ];



    public function sync_id($sync_id)
    {
        if ($sync_id !== null) {
            $this->builder->where('sync_id', $sync_id);
        }
        return $this;
    }
}
