<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');

        $Role = Role::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        return $this->responseSuccess('Role display successfully', $Role);
    }

    public function store(RoleRequest $request)
    {
        $create_role = Role::create([
            "name" => $request->name,
            "access_permission" => $request->access_permission,
        ]);

        return $this->responseCreated('Role Successfully Created', $create_role);
    }

    public function update(RoleRequest $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $previousName = $role->name;

        $role->name = $request['name'];
        $role->access_permission = $request['access_permission'];

        if (!$role->isDirty()) {
            return $this->responseSuccess('No Changes', $role);
        }

        // Save updated role
        $role->save();

        // Update access token abilities in personal_access_tokens
        DB::table('personal_access_tokens')
            ->where('name', $previousName) // Match with previous role name
            ->update([
                'abilities' => json_encode($request['access_permission']),
                'name' => $request['name'] // Update token name as well
            ]);

        return $this->responseSuccess('Role successfully updated', $role);
    }


    public function archived(Request $request, $id)
    {
        $role = Role::withTrashed()->find($id);

        if (!$role) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }

        if ($role->deleted_at) {

            $role->restore();

            return $this->responseSuccess('Role successfully restore', $role);
        }

        if (User::where('role_id', $id)->exists()) {
            return $this->responseUnprocessable('', 'Unable to Archive, Role already in used!');
        }

        if (!$role->deleted_at) {

            $role->delete();

            return $this->responseSuccess('Role successfully archive', $role);
        }
    }
}
