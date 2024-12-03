<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\User;

class UserController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');

        $User = User::with(['company'])
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            UserResource::collection($User);
        } else {
            $User = UserResource::collection($User);
        }
        return $this->responseSuccess('User display successfully', $User);
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
            return $this->responseUnprocessable('', 'Unable to Archive, User already in used!');
        }

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
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
}
