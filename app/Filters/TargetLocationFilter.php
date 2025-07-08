<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class TargetLocationFilter extends QueryFilters
{
    protected array $columnSearch = [
        'id',
        'title',
        'region',
        'province',
        'city_municipality',
        'sub_municipality',
        'barangay',
    ];


    public function is_final($is_final)
    {
        if ($is_final == 1) {
            $this->builder->where('is_final', $is_final);
        }
        return $this;
    }

    public function is_done($is_done)
    {
        if ($is_done == 1) {
            $this->builder->where('is_done', $is_done);
        }
        return $this;
    }
}
