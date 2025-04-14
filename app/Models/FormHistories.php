<?php

namespace App\Models;

use App\Filters\FormHistoriesFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormHistories extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'title',
        'description',
        'sections',
    ];

    protected $casts = [
        'sections' => 'json',
    ];


    protected string $default_filters = FormHistoriesFilter::class;

}
