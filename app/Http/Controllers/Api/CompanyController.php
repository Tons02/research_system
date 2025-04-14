<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CompanyRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Companies;
use App\Events\PublicChannelEventSyncingCompanies;

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

        Companies::upsert(
            $request->input('companies'),
            ['sync_id'],
            ['company_code', 'company_name', 'updated_at', 'deleted_at']
        );

        // event(new PublicChannelEventSyncingCompanies("Laravel Reverb Sync Companies"));
        return $this->responseSuccess('Sync companies successfully');
    }
}
