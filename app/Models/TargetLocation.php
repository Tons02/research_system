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
        'bound_box',
        'response_limit',
        'form_id',
        'form_history_id',
        'is_done',
        'is_final',
        'vehicle_counted_by_user_id',
        'foot_counted_by_user_id',
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
        'region_psgc_id' => 'string',
        'province_psgc_id' => 'string',
        'city_municipality_psgc_id' => 'string',
        'sub_municipality_psgc_id' => 'string',
        'barangay_psgc_id' => 'string',
        'is_final' => 'boolean',
        'is_done' => 'boolean',
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
        )->withPivot(['response_limit', 'is_done']);
    }


    public function vehicle_counted_by_user()
    {
        return $this->belongsTo(User::class, 'vehicle_counted_by_user_id')->withTrashed();
    }

    public function foot_counted_by_user()
    {
        return $this->belongsTo(User::class, 'foot_counted_by_user_id')->withTrashed();
    }
}
