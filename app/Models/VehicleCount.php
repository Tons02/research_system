<?php

namespace App\Models;

use App\Filters\VehicleCountFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleCount extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $fillable = [
        'date',
        'time',
        'time_period',
        'total_left',
        'total_right',
        'grand_total',
        'surveyor_id',
        'surveyor_id',
    ];

    protected $hidden = [
        "updated_at",
    ];

    protected string $default_filters = VehicleCountFilter::class;


    public function target_locations()
    {
        return $this->belongsToMany(
            TargetLocation::class,
            "target_locations_vehicle_counts",
            "vehicle_count_id",
            "target_location_id",
            "id",
            "id"
        );
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyor_id')->withTrashed();
    }
}
