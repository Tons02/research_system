<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class SurveyAnswerFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        'name'
    ];

    public function target_location_id($target_location_id)
    {
        $this->builder->where('target_location_id', $target_location_id);

        return $this;
    }

    public function surveyor_id($surveyor_id)
    {
        if ($surveyor_id !== null) {
            $this->builder->where('surveyor_id', $surveyor_id);
        }
        return $this;
    }
}
