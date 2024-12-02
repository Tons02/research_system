<?php

namespace App\Models;

use App\Filters\SubUnitFilter;
use Illuminate\Database\Eloquent\Model;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\softDeletes;
use App\Models\Location;
use App\Models\Unit;

class SubUnit extends Model
{
    use softDeletes, Filterable;
    
    protected $fillable = [
        'sync_id',
        'sub_unit_code',
        'sub_unit_name',
        'unit_id',
        'updated_at',
        'deleted_at',
    ];

    protected string $default_filters = SubUnitFilter::class;

    
    public function unit()
    {
        return $this->belongsTo(Companies::class, 'unit_id', 'sync_id');
    }

    public function locations()
    {
        return $this->belongsToMany(
            Location::class,
            "locations_sub_unit",
            "sub_unit_id",
            "location_id",
            "sync_id",
            "sync_id"
        );
    }
}
