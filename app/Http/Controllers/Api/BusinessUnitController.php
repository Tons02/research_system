<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\BusinessUnitRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\BusinessUnit;

class BusinessUnitController extends Controller
{
    use ApiResponse;
    
    public function index(Request $request){
        $status = $request->query('status');
        
        $Companies = BusinessUnit::
        when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();

        return $this->responseSuccess('Companies display successfully', $Companies);
    }

    public function store(BusinessUnitRequest $request){

        BusinessUnit::upsert(
            $request->input('business_unit'),               
            ['sync_id'],             
            ['business_unit_code', 'business_unit_name', 'company_id', 'updated_at', 'deleted_at'] 
        );

        return $this->responseSuccess('Sync business unit successfully');
    }
}