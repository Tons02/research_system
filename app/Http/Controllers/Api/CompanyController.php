<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CompanyRequest;
use Illuminate\Support\Facades\Http;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Companies;

class CompanyController extends Controller
{
    use ApiResponse;
    public function index(Request $request){
        $status = $request->query('status');
        
        $Companies = Companies::
        when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();

        return $this->responseSuccess('Companies display successfully', $Companies);
    }

    public function store(CompanyRequest $request){
    
        $companies = $request->input('companies');

        Companies::upsert(
            $companies,               
            ['sync_id'],             
            ['company_code', 'company_name', 'updated_at', 'deleted_at'] 
        );

        return $this->responseSuccess('Sync companies successfully');
    }
}
