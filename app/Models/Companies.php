<?php

namespace App\Models;

use App\Filters\CompanyFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;

class Companies extends Model
{
    use softDeletes, Filterable;
    
    protected $fillable = [
        'sync_id',
        'company_code',
        'company_name',
        'updated_at',
        'deleted_at',
    ];

    protected string $default_filters = CompanyFilter::class;
}
