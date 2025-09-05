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
        'target_location_id',
        'date',
        'time_range',
        'time_period',
        'total_left_male',
        'total_right_male',
        'total_male',
        'total_left_female',
        'total_right_female',
        'total_female',
        'grand_total',
        'surveyor_id',
        'sync_at',
        'created_at'
    ];

    protected $hidden = [
        "updated_at",
    ];

    protected string $default_filters = FootCountFilter::class;

    public function target_locations()
    {
        return $this->belongsTo(TargetLocation::class, 'target_location_id')->withTrashed();
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyor_id')->withTrashed();
    }
}
