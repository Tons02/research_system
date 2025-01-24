<?php

namespace App\Models;

use App\Filters\FormFilter;
use Illuminate\Database\Eloquent\Model;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\softDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Form extends Model
{
    use HasFactory, softDeletes, Filterable;

    protected $fillable = [
        'title',
        'description',
        'sections',
    ];

    protected $hidden = [
        "updated_at",
    ];

    protected string $default_filters = FormFilter::class;

    protected $casts = [
        'sections' => 'json',
    ];
}
