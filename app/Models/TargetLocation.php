<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
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
        'form_history_id',
        'is_done',
    ];

    public function form()
    {
        return $this->belongsTo(FormHistories::class, 'form_history_id')->withTrashed();
    }

    protected string $default_filters = TargetLocationFilter::class;

    protected $casts = [
        'bound_box' => 'json',
        'region_psgc_id' => 'string',
        'province_psgc_id' => 'string',
        'city_municipality_psgc_id' => 'string',
        'sub_municipality_psgc_id' => 'string',
        'barangay_psgc_id' => 'string',
    ];

    public function target_locations_users()
    {
        return $this->belongsToMany(
            User::class,
            "target_locations_users",
            "target_location_id",
            "user_id",
            "id",
            "id"
        )->withPivot('response_limit');
    }

}
