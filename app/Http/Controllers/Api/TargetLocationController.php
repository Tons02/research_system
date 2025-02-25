<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TargetLocationResource;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\TargetLocation;
use App\Http\Requests\TargetLocationRequest;

class TargetLocationController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');

        $TargetLocation = TargetLocation::with(['form'])
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            TargetLocationResource::collection($TargetLocation);
        } else {
            $TargetLocation = TargetLocationResource::collection($TargetLocation);
        }
        return $this->responseSuccess('Target Location display successfully', $TargetLocation);
    }

    public function store(TargetLocationRequest $request)
    {
        $create_target_location = TargetLocation::create([
            "target_location" => $request["target_location"],
            "form_id" => $request["form_id"],
        ]);

        return $this->responseCreated('Form Successfully Created', $create_target_location);
    }

    public function update(TargetLocationRequest $request, $id)
    {
        $target_location_id = TargetLocation::find($id);

        if (!$target_location_id) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $target_location_id->target_location = $request['target_location'];
        $target_location_id->form_id = $request['form_id'];

        if (!$target_location_id->isDirty()) {
            return $this->responseSuccess('No Changes', $target_location_id);
        }

        $target_location_id->save();

        return $this->responseSuccess('Role successfully updated', $target_location_id);
    }

    public function archived(Request $request, $id)
    {
        $target_location = TargetLocation::withTrashed()->find($id);

        if (!$target_location) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }

        if ($target_location->deleted_at) {

            $target_location->restore();

            return $this->responseSuccess('Target Location successfully restore', $target_location);
        }

        if (!$target_location->deleted_at) {

            $target_location->delete();

            return $this->responseSuccess('Target Location successfully archive', $target_location);
        }
    }


}
