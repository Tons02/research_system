<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Filters\UserFilter;
use Laravel\Sanctum\HasApiTokens;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\softDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Companies;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\Location;
use App\Models\Role;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, softDeletes, Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_prefix',
        'id_no',
        'first_name',
        'middle_name',
        'last_name',
        'mobile_number',
        'gender',
        'one_charging_sync_id',
        'username',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role_id' => 'integer',
        ];
    }


    protected string $default_filters = UserFilter::class;

    public function one_charging()
    {
        return $this->belongsTo(OneCharging::class, 'one_charging_sync_id', 'sync_id')->withTrashed();
    }


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id')->withTrashed();
    }

    public function target_locations_users()
    {
        return $this->belongsToMany(
            TargetLocation::class,
            'target_locations_users',
            'user_id',
            'target_location_id',
            'id',
            'id'
        )
            ->withPivot('response_limit', 'is_done')
            ->wherePivot('is_done', false)
            ->where('is_final', true)
            ->where('start_date', '<', Carbon::now());
    }

    public function target_locations_users_history()
    {
        return $this->belongsToMany(
            TargetLocation::class,
            "target_locations_users",
            "user_id",
            "target_location_id",
            "id",
            "id"
        )
            ->withPivot('response_limit', 'is_done')
            ->wherePivot('is_done', true)
            ->where('is_final', true);
    }

    public function vehicle_counted()
    {
        return $this->hasMany(TargetLocation::class, 'vehicle_counted_by_user_id')
            ->where('is_final', 1)
            ->where('is_done', 0)
            ->withTrashed()
            ->where('start_date', '<', Carbon::now());
    }

    public function foot_counted()
    {
        return $this->hasMany(TargetLocation::class, 'foot_counted_by_user_id')
            ->where('is_final', 1)
            ->where('is_done', 0)
            ->withTrashed()
            ->where('start_date', '<', Carbon::now());;
    }
}
