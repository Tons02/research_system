<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormHistories;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;

class FormHistoriesController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $status = $request->query('status');

        $Form = FormHistories::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        return $this->responseSuccess('Form history display successfully', $Form);
    }

    public function show(Request $request, $id)
    {
        $status = $request->query('status');

        $Form = FormHistories::where('id', $id)->get();

        if($Form->isEmpty()){
            return $this->responseUnprocessable('', 'Invalid ID');
        }

        return $this->responseSuccess('Single form history display successfully', $Form);
    }
}
