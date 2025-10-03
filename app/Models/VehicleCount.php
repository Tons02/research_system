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
        'time_range',
        'time_period',
        'total_left_private_car',
        'total_left_truck',
        'total_left_jeepney',
        'total_left_bus',
        'total_left_tricycle',
        'total_left_bicycle',
        'total_left_e_bike',
        'total_left',
        'total_right_private_car',
        'total_right_truck',
        'total_right_jeepney',
        'total_right_bus',
        'total_right_tricycle',
        'total_right_bicycle',
        'total_right_e_bike',
        'total_right',
        'grand_total',
        'target_location_id',
        'surveyor_id',
        'sync_at',
        'created_at',
    ];

    protected $hidden = [
        "updated_at",
    ];

    protected string $default_filters = VehicleCountFilter::class;


    public function target_locations()
    {
        return $this->belongsTo(TargetLocation::class, 'target_location_id')->withTrashed();
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyor_id')->withTrashed();
    }

    protected $casts = [
        'total_left_private_car' => 'integer',
        'total_right_private_car' => 'integer',
        'total_left_truck' => 'integer',
        'total_right_truck' => 'integer',
        'total_left_jeepney' => 'integer',
        'total_right_jeepney' => 'integer',
        'total_right_bus' => 'integer',
        'total_left_tricycle' => 'integer',
        'total_right_tricycle' => 'integer',
        'total_left_bicycle' => 'integer',
        'total_right_bicycle' => 'integer',
        'total_left_e_bike' => 'integer',
        'total_right_e_bike' => 'integer',
    ];
}
