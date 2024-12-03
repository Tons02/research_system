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
        'company_id',
        'business_unit_id',
        'department_id',
        'unit_id',
        'sub_unit_id',
        'location_id',
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

    public function company()
    {
        return $this->belongsTo(Companies::class, 'company_id')->withTrashed();
    }

    public function business_unit()
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id')->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id')->withTrashed();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id')->withTrashed();
    }

    public function sub_unit()
    {
        return $this->belongsTo(SubUnit::class, 'sub_unit_id')->withTrashed();
    }


    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id')->withTrashed();
    }


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id')->withTrashed();
    }
}
