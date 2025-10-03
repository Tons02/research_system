<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use Essa\APIToolKit\Filters\Filterable;
use App\Filters\TargetLocationFilter;
use App\Models\TargetLocationUsers;

class TargetLocation extends Model
{
    use softDeletes, Filterable;

    protected $fillable = [
        'title',
        'region_psgc_id',
        'region',
        'province_psgc_id',
        'province',
        'city_municipality_psgc_id',
        'city_municipality',
        'sub_municipality_psgc_id',
        'sub_municipality',
        'barangay_psgc_id',
        'barangay',
        'street',
        'mobile_locations',
        'bound_box',
        'response_limit',
        'form_id',
        'form_history_id',
        'is_done',
        'is_final',
        'start_date',
        'end_date',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id')->withTrashed();
    }

    public function form_histories()
    {
        return $this->belongsTo(FormHistories::class, 'form_history_id')->withTrashed();
    }

    protected string $default_filters = TargetLocationFilter::class;

    protected $casts = [
        'bound_box' => 'json',
        'mobile_locations' => 'json',
        'region_psgc_id' => 'string',
        'province_psgc_id' => 'string',
        'city_municipality_psgc_id' => 'string',
        'sub_municipality_psgc_id' => 'string',
        'barangay_psgc_id' => 'string',
        'is_final' => 'boolean',
        'is_done' => 'boolean',
    ];
}
