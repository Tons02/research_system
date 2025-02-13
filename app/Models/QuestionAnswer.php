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
