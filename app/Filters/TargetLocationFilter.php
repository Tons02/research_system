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


    public function is_final($is_final)
    {
        if ($is_final !== null) {
            $this->builder->where('is_final', $is_final);
        }
        return $this;
    }

    public function is_done($is_done)
    {
        if ($is_done !== null) {
            $this->builder->where('is_done', $is_done);
        }
        return $this;
    }
}
