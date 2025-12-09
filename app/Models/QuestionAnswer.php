<?php

namespace App\Models;

use App\Filters\QuestionAnswerFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAnswer extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'survey_id',
        'income_class',
        'sub_income_class',
        'monthly_utility_expenses',
        'sub_monthly_utility_expenses',
        'section',
        'question_type',
        'question',
        'answer',
    ];

    protected $casts = [
        'answer' => 'json',
    ];

    protected string $default_filters = QuestionAnswerFilter::class;

    public function survey()
    {
        return $this->belongsTo(SurveyAnswer::class, 'survey_id');
    }
}
