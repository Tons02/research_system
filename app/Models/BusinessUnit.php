<?php

namespace App\Models;

use App\Filters\BusinessUnitFilter;
use Illuminate\Database\Eloquent\Model;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\softDeletes;

class BusinessUnit extends Model
{
    use softDeletes, Filterable;
    
    protected $fillable = [
        'sync_id',
        'business_unit_code',
        'business_unit_name',
        'company_id',
        'updated_at',
        'deleted_at',
    ];


    protected string $default_filters = BusinessUnitFilter::class;
}
