<?php

namespace App\Models;

use App\Filters\LocationFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
// use App\Models\BusinessUnit;

class Location extends Model
{
    use softDeletes, Filterable;

    protected $fillable = [
        'sync_id',
        'location_code',
        'location_name',
        'updated_at',
        'deleted_at',
    ];

    protected string $default_filters = LocationFilter::class;

    public function sub_unit()
    {
        return $this->belongsToMany(
            SubUnit::class,
            "locations_sub_unit",
            "location_id",
            "sub_unit_id",
            "sync_id",
            "sync_id"
        );
    }
}
