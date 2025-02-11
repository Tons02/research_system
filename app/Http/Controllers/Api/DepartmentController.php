<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DepartmentRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Department;

class DepartmentController extends Controller
{
    use ApiResponse;
    public function index(Request $request){
        $status = $request->query('status');

        $departments = Department::
        with('business_unit')
        ->when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();

        return $this->responseSuccess('Departments display successfully', $departments);
    }

    public function store(DepartmentRequest $request){

        Department::upsert(
            $request->input('departments'),
            ['sync_id'],
            ['department_code', 'department_name', 'business_unit_id', 'updated_at', 'deleted_at']
        );

        return $this->responseSuccess('Sync Department successfully');
    }
}
