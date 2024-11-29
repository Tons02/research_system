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
        'sub_units',
        'updated_at',
        'deleted_at',
    ];

    protected string $default_filters = LocationFilter::class;

    public function business_unit()
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id', 'sync_id');
    }
}
