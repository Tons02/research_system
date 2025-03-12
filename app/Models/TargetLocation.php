<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use App\Models\Form;
use Essa\APIToolKit\Filters\Filterable;
use App\Filters\TargetLocationFilter;

class TargetLocation extends Model
{
    use softDeletes, Filterable;

    protected $fillable = [
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
        'bound_box',
        'response_limit',
        'form_id',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id')->withTrashed();
    }

    protected string $default_filters = TargetLocationFilter::class;

    protected $casts = [
        'bound_box' => 'json',
    ];

}
