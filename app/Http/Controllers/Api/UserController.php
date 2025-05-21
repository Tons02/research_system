<?php

namespace App\Http\Controllers\Api;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\TargetLocation;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');
        $target_location_users = $request->query('target_location_users');
        $target_location_id_users_update = $request->query('target_location_id_users_update');

        $users = User::query()
            ->when($status === 'inactive', function ($query) {
                $query->onlyTrashed();
            })
            ->when($target_location_users == 1, function ($query) {
                $query
                    // Exclude users with any unfinished target_locations_users
                    ->whereNotIn('id', function ($q) {
                        $q->select('user_id')
                            ->from('target_locations_users')
                            ->where('is_done', 0)
                            ->where('deleted_at', null);
                    })
                    // Exclude unfinished vehicle counters
                    ->whereNotIn('id', function ($q) {
                        $q->select('vehicle_counted_by_user_id')
                            ->from('target_locations')
                            ->where('is_done', 0)
                            ->whereNotNull('vehicle_counted_by_user_id')
                            ->where('deleted_at', null);
                    })
                    // Exclude unfinished foot counters
                    ->whereNotIn('id', function ($q) {
                        $q->select('foot_counted_by_user_id')
                            ->from('target_locations')
                            ->where('is_done', 0)
                            ->whereNotNull('foot_counted_by_user_id')
                            ->where('deleted_at', null);
                    });
            })
            ->when($target_location_id_users_update, function ($query) use ($target_location_id_users_update) {
                $query
                    // Include users assigned as unfinished vehicle counters
                    ->orWhereExists(function ($q) use ($target_location_id_users_update) {
                        $q->select(DB::raw(1))
                            ->from('target_locations_users as tlu')
                            ->join('target_locations as tl', 'tl.id', '=', 'tlu.target_location_id')
                            ->whereColumn('users.id', 'tlu.user_id')
                            ->where('tlu.is_done', 0)
                            ->where('tl.is_final', 0)
                            ->whereIn('tl.id', (array) $target_location_id_users_update);
                    })
                    // Include users assigned as unfinished vehicle counters
                    ->orWhereIn('id', function ($q) use ($target_location_id_users_update) {
                        $q->select('vehicle_counted_by_user_id')
                            ->from('target_locations')
                            ->where('is_final', 0)
                            ->whereIn('id', (array) $target_location_id_users_update)
                            ->whereNotNull('vehicle_counted_by_user_id');
                    })
                    // Include users assigned as unfinished foot counters
                    ->orWhereIn('id', function ($q) use ($target_location_id_users_update) {
                        $q->select('foot_counted_by_user_id')
                            ->from('target_locations')
                            ->where('is_final', 0)
                            ->whereIn('id', (array) $target_location_id_users_update)
                            ->whereNotNull('foot_counted_by_user_id');
                    })
                    // Include users assigned as unfinished vehicle counters
                    ->orWhereExists(function ($q) use ($target_location_id_users_update) {
                        $q->select(DB::raw(1))
                            ->from('target_locations_users as tlu')
                            ->join('target_locations as tl', 'tl.id', '=', 'tlu.target_location_id')
                            ->whereColumn('users.id', 'tlu.user_id')
                            ->where('tlu.is_done', 0)
                            ->where('tl.is_final', 1)
                            ->whereIn('tl.id', (array) $target_location_id_users_update)
                        ;
                    })
                    // Include users assigned as unfinished vehicle counters
                    ->orWhereIn('id', function ($q) use ($target_location_id_users_update) {
                        $q->select('vehicle_counted_by_user_id')
                            ->from('target_locations')
                            ->where('is_final', 1)
                            ->whereIn('id', (array) $target_location_id_users_update)
                            ->whereNotNull('vehicle_counted_by_user_id')
                        ;
                    })
                    // Include users assigned as unfinished foot counters
                    ->orWhereIn('id', function ($q) use ($target_location_id_users_update) {
                        $q->select('foot_counted_by_user_id')
                            ->from('target_locations')
                            ->where('is_final', 1)
                            ->whereIn('id', (array) $target_location_id_users_update)
                            ->whereNotNull('foot_counted_by_user_id')
                        ;
                    })
                    ->withTrashed();
            })
            // ✅ Conditional eager loading
            ->with(['target_locations_users' => function ($query) use ($target_location_users) {
                if ($target_location_users == 1) {
                    $query->where('target_locations_users.is_done', 1);
                }
            }])

            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();




        if (!$pagination) {
            UserResource::collection($users);
        } else {
            $users = UserResource::collection($users);
        }
        return $this->responseSuccess('User display successfully', $users);
    }

    public function store(UserRequest $request)
    {
        $create_user = User::create([
            "id_prefix" => $request["personal_info"]["id_prefix"],
            "id_no" => $request["personal_info"]["id_no"],
            "first_name" => $request["personal_info"]["first_name"],
            "middle_name" => $request["personal_info"]["middle_name"],
            "last_name" => $request["personal_info"]["last_name"],
            "mobile_number" => $request["personal_info"]["mobile_number"],
            "gender" => $request["personal_info"]["gender"],

            "company_id" => $request["personal_info"]["company_id"],
            "business_unit_id" => $request["personal_info"]["business_unit_id"],
            "department_id" => $request["personal_info"]["department_id"],
            "unit_id" => $request["personal_info"]["unit_id"],
            "sub_unit_id" => $request["personal_info"]["sub_unit_id"],
            "location_id" => $request["personal_info"]["location_id"],

            "username" => $request["username"],
            "password" => $request["username"],

            "role_id" => $request["role_id"],
        ]);

        return $this->responseCreated('User Successfully Created', $create_user);
    }

    public function update(UserRequest $request, $id)
    {
        $userID = User::find($id);

        if (!$userID) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $userID->mobile_number = $request["personal_info"]["mobile_number"];
        $userID->company_id = $request["personal_info"]["company_id"];
        $userID->business_unit_id = $request["personal_info"]["business_unit_id"];
        $userID->department_id = $request["personal_info"]["department_id"];
        $userID->unit_id = $request["personal_info"]["unit_id"];
        $userID->sub_unit_id = $request["personal_info"]["sub_unit_id"];
        $userID->location_id = $request["personal_info"]["location_id"];
        $userID->username = $request['username'];
        $userID->role_id = $request['role_id'];

        if (!$userID->isDirty()) {
            return $this->responseSuccess('No Changes', $userID);
        }

        $userID->save();

        return $this->responseSuccess('Users successfully updated', $userID);
    }

    public function archived(Request $request, $id)
    {
        if ($id == auth('sanctum')->user()->id) {
            return $this->responseUnprocessable('', 'Unable to archive. You cannot archive your own account.');
        }

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }
        if (DB::table('target_locations_users')
            ->where('user_id', $id)
            ->where('is_done', 0)
            ->where('deleted_at', null)
            ->exists()
        ) {
            return $this->responseUnprocessable('', 'Unable to archive. The user is tagged to an active target location.');
        }

        if (TargetLocation::orwhere('vehicle_counted_by_user_id', $id)
            ->where('is_done', 0)
            ->orwhere('foot_counted_by_user_id', $id)
            ->exists()
        ) {
            return $this->responseUnprocessable('', 'Unable to archive. The user is tagged to an active target location.');
        }

        if ($user->deleted_at) {

            $user->restore();
            return $this->responseSuccess('user successfully restore', $user);
        }

        if (!$user->deleted_at) {

            $user->delete();
            return $this->responseSuccess('user successfully archive', $user);
        }
    }

    public function export(Request $request)
    {
        $status = $request->query('status', null);
        $title = $status == "inactive" ? 'inactive-users.xlsx' : 'users.xlsx';
        return Excel::download(new UsersExport($status), $title);
    }
}
