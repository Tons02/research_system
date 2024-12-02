<?php

namespace App\Models;

use App\Filters\RoleFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory, softDeletes, Filterable;
        protected $fillable = [
            'id',
            'name',
            'access_permission',
            'is_active',
        ];

        protected $hidden = [
            "updated_at", 
            "deleted_at"
        ];

        protected string $default_filters = RoleFilter::class;

        protected $casts = [
            'access_permission' => 'json',
            'is_active' => 'boolean'
        ];
}
