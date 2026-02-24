<?php

namespace App\Models;

use App\Filters\PendingUserFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingUser extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'id_prefix',
        'id_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'username',
        'password',
    ];

    protected $hidden = [
        "updated_at",
    ];

    protected string $default_filters = PendingUserFilter::class;
}
