<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TargetLocationResource;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\TargetLocation;
use App\Http\Requests\TargetLocationRequest;
use App\Models\SurveyAnswer;
use Illuminate\Support\Facades\Cache;

class TargetLocationController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');

        // Generate a unique cache key based on status
        $cacheKey = "target_location_api_" . ($status ?? 'active');

        // Try to get data from cache, otherwise store it in Redis
        $TargetLocation = Cache::remember($cacheKey, 600, function () use ($status) {
            return TargetLocation::with('form')
                ->when($status === "inactive", function ($query) {
                    $query->onlyTrashed();
                })
                ->orderBy('created_at', 'desc')
                ->useFilters()
                ->dynamicPaginate();
        });

        // Transform data with resource
        $TargetLocation = TargetLocationResource::collection($TargetLocation);

        return $this->responseSuccess('Target Location display successfully', $TargetLocation);
    }

    public function store(TargetLocationRequest $request)
    {
        $create_target_location = TargetLocation::create([
            "target_location" => $request["target_location"],
            "form_id" => $request["form_id"],
        ]);

        // Clear relevant caches
        $this->clearTargetLocationCache();

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

        // Clear relevant caches
        $this->clearTargetLocationCache();

        return $this->responseSuccess('Target Location successfully updated', $target_location_id);
    }

    public function archived(Request $request, $id)
    {
        $target_location = TargetLocation::withTrashed()->find($id);

        if (!$target_location) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }

        if ($target_location->deleted_at) {

            $target_location->restore();

            // Clear relevant caches
            $this->clearTargetLocationCache();

            return $this->responseSuccess('Target Location successfully restore', $target_location);
        }

         // need to put this one once the survey answer is already created
         if (SurveyAnswer::where('target_location_id', $id)->exists()) {

            return $this->responseUnprocessable('', 'Unable to Archive, Target location already in used!');
        }

        if (!$target_location->deleted_at) {

            $target_location->delete();

            // Clear relevant caches
            $this->clearTargetLocationCache();

            return $this->responseSuccess('Target Location successfully archive', $target_location);
        }
    }

    private function clearTargetLocationCache()
    {
        Cache::forget("target_location_api_active");
        Cache::forget("target_location_api_inactive");
    }



}
