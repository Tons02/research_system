<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Role;

class RoleController extends Controller
{
    use ApiResponse;
    public function index(Request $request){
        $status = $request->query('status');
        
        $Role = Role::
        when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();
        
        return $this->responseSuccess('Role display successfully', $Role );
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
        $role_id = Role::find($id);

        if (!$role_id) {
            return $this->responseNotFound('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $role_id->name = $request['name'];
        $role_id->access_permission = $request['access_permission'];

        if (!$role_id->isDirty()) { 
            return $this->responseSuccess('No Changes', $role_id);
        }

        $role_id->save();
        
        return $this->responseSuccess('Role successfully updated', $role_id);
    }

    public function archived(Request $request, $id)
    {
        $role = Role::withTrashed()->find($id);

        if (!$role) {
             return $this->responseNotFound('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }
        
        if ($role->deleted_at) {

            $role->restore();

            return $this->responseSuccess('Role successfully restore', $role);
        }

        if (!$role->deleted_at) {

            $role->delete();

            return $this->responseSuccess('Role successfully archive', $role);

        } 
    }
}
