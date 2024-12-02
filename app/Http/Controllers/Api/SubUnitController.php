<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SubUnitRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\SubUnit;

class SubUnitController extends Controller
{
    use ApiResponse;
    
    public function index(Request $request){
        $status = $request->query('status');
        
        $SubUnit = SubUnit::
        with(['unit', 'locations'])
        ->when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();
        
        return $this->responseSuccess('Sub units display successfully', $SubUnit );
    }

    public function store(SubUnitRequest $request){

        SubUnit::upsert(
            $request->input('sub_units'),               
            ['sync_id'],             
            ['sub_unit_code', 'sub_unit_name', 'unit_id', 'updated_at', 'deleted_at'] 
        );

        return $this->responseSuccess('Sync Sub unit successfully');
    }
}
