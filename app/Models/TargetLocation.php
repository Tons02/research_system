<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use App\Models\Form;
use Essa\APIToolKit\Filters\Filterable;
use App\Filters\TargetLocationFilter;

class TargetLocation extends Model
{
    use softDeletes, Filterable;

    protected $fillable = [
        'target_location',
        'form_id',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id')->withTrashed();
    }

    protected string $default_filters = TargetLocationFilter::class;

}
