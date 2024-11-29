<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UnitRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Unit;

class UnitController extends Controller
{
    use ApiResponse;
    public function index(Request $request){
        $status = $request->query('status');
        
        $units = Unit::
        with('department')
        ->when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();

        return $this->responseSuccess('Units display successfully', $units);
    }

    public function store(UnitRequest $request){

        Unit::upsert(
            $request->input('units'),               
            ['sync_id'],             
            ['unit_code', 'unit_name', 'department_id', 'updated_at', 'deleted_at'] 
        );

        return $this->responseSuccess('Sync Units unit successfully');
    }
}
