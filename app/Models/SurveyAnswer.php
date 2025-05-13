<?php

namespace App\Models;

use App\Filters\SurveyAnswerFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyAnswer extends Model
{
    use SoftDeletes, Filterable;

    protected $fillable = [
        'target_location_id',
        'name',
        'age',
        'gender',
        'address',
        'contact_number',
        'date',
        'family_size',
        'income_class',
        'sub_income_class',
        'monthly_utility_expenses',
        'sub_monthly_utility_expenses',
        'educational_attainment',
        'employment_status',
        'occupation',
        'structure_of_house',
        'ownership_of_house',
        'house_rent',
        'questionnaire_answer',
        'surveyor_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'surveyor_id')->withTrashed();
    }

    public function target_location()
    {
        return $this->belongsTo(TargetLocation::class, 'target_location_id')->withTrashed();
    }


    protected string $default_filters = SurveyAnswerFilter::class;

    protected $casts = [
        'questionnaire_answer' => 'json',
    ];
}
