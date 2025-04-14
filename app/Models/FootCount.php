<?php

namespace App\Models;

use App\Filters\FootCountFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FootCount extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $fillable = [
        'date',
        'time',
        'time_period',
        'total_male',
        'total_female',
        'grand_total',
    ];

    protected $hidden = [
        "updated_at",
    ];

    protected string $default_filters = FootCountFilter::class;


    public function target_locations()
    {
        return $this->belongsToMany(
            TargetLocation::class,
            "target_locations_foot_counts",
            "foot_count_id",
            "target_location_id",
            "id",
            "id"
        );
    }
}
