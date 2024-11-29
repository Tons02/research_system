<?php

namespace App\Models;


use App\Filters\UnitFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use App\Models\Department;

class Unit extends Model
{
    use softDeletes, Filterable;
    
    protected $fillable = [
        'sync_id',
        'unit_code',
        'unit_name',
        'department_id',
        'updated_at',
        'deleted_at',
    ];

    protected string $default_filters = UnitFilter::class;

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'sync_id');
    }
}
