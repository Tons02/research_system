<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetLocationUsers extends Pivot
{
    use SoftDeletes;

    protected $table = 'target_locations_users';

    protected $dates = ['deleted_at'];
}
