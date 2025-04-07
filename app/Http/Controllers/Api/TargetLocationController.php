<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TargetLocationResource;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\TargetLocation;
use App\Http\Requests\TargetLocationRequest;
use App\Models\Form;
use App\Models\FormHistories;
use App\Models\SurveyAnswer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TargetLocationController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');


        $TargetLocation = TargetLocation::
        when($status === "inactive", function ($query) {
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
        DB::beginTransaction(); // Start the transaction

        try {
            // Fetch form, return error if not found
            $form = Form::find($request["form_id"]);
            if (!$form) {
                return $this->responseNotFound('Form not found');
            }

            // Create form history
            $create_form_history = FormHistories::create([
                "title" => $form->title,
                "description" => $form->description,
                "sections" => $form->sections,
            ]);

            // Create target location
            $create_target_location = TargetLocation::create([
                "region_psgc_id" => $request["region_psgc_id"],
                "region" => $request["region"],
                "province_psgc_id" => $request["province_psgc_id"],
                "province" => $request["province"],
                "city_municipality_psgc_id" => $request["city_municipality_psgc_id"],
                "city_municipality" => $request["city_municipality"],
                "sub_municipality_psgc_id" => $request["sub_municipality_psgc_id"],
                "sub_municipality" => $request["sub_municipality"],
                "barangay_psgc_id" => $request["barangay_psgc_id"],
                "barangay" => $request["barangay"],
                "street" => $request["street"],
                "bound_box" => $this->getBoundBox(implode(', ', array_filter([
                    $request["barangay"],
                    $request["city_municipality"],
                    $request["province"],
                    $request["region"],
                    'Philippines'
                ]))) ?? [],
                "response_limit" => $request["response_limit"],
                "form_history_id" => $create_form_history->id,
                "is_done" => 0,
            ]);

            // Attach surveyors to the target location (using the pivot table with additional attributes)
            foreach ($request['surveyors'] as $surveyor) {
                $create_target_location->target_locations_users()->attach(
                    $surveyor['user_id'],
                    ['response_limit' => $surveyor['response_limit']],
                );
            }

            DB::commit();
            return $this->responseCreated('Form Successfully Created', $create_target_location);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if an error occurs
            return $this->responseServerError('Network Error Please Try Again');
        }
    }


    public function update(TargetLocationRequest $request, $id)
    {
        DB::beginTransaction(); // Start the transaction

        try {

        $target_location = TargetLocation::find($id); // Correct variable assignment

        if (!$target_location) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        // Update the target location details
        $target_location->region_psgc_id = $request['region_psgc_id'];
        $target_location->region = $request['region'];
        $target_location->province_psgc_id = $request['province_psgc_id'];
        $target_location->province = $request['province'];
        $target_location->city_municipality_psgc_id = $request['city_municipality_psgc_id'];
        $target_location->city_municipality = $request['city_municipality'];
        $target_location->sub_municipality_psgc_id = $request['sub_municipality_psgc_id'];
        $target_location->sub_municipality = $request['sub_municipality'];
        $target_location->barangay_psgc_id = $request['barangay_psgc_id'];
        $target_location->barangay = $request['barangay'];
        $target_location->street = $request['street'];
        $target_location->response_limit = $request['response_limit'];
        $target_location->bound_box = $this->getBoundBox(implode(', ', array_filter([
            $request["barangay"],
            $request["city_municipality"],
            $request["province"],
            $request["region"],
            'Philippines'
        ]))) ?? [];

        // Track if any of the target location fields changed
        $targetLocationUpdated = $target_location->isDirty();

        // Check if surveyors pivot table has changes
        $existingSurveyorIds = $target_location->target_locations_users->pluck('id')->toArray();
        $pivotChanged = false;

        // Loop through the surveyors provided in the request
        foreach ($request['surveyors'] as $surveyor) {
            // If the surveyor already exists in the pivot table
            if (in_array($surveyor['user_id'], $existingSurveyorIds)) {
                // Check if the response_limit has changed
                $pivot = $target_location->target_locations_users()->where('user_id', $surveyor['user_id'])->first();
                if ($pivot && $pivot->pivot->response_limit != $surveyor['response_limit']) {
                    // If the response_limit is different, update the pivot
                    $target_location->target_locations_users()->updateExistingPivot(
                        $surveyor['user_id'],
                        ['response_limit' => $surveyor['response_limit']]
                    );
                    $pivotChanged = true; // Mark that the pivot has changed
                }
            } else {
                // If the surveyor does not exist, attach it
                $target_location->target_locations_users()->attach(
                    $surveyor['user_id'],
                    ['response_limit' => $surveyor['response_limit']]
                );
                $pivotChanged = true; // Mark that the pivot has changed
            }
        }

        // If there were no changes at all
        if (!$targetLocationUpdated && !$pivotChanged) {
            return $this->responseSuccess('No Changes', $target_location);
        }

        // Save target location and pivot changes
        $target_location->save();

        DB::commit();
        return $this->responseSuccess('Target Location successfully updated', $target_location);
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback transaction if an error occurs
        return $this->responseServerError('Network Error Please Try Again');
    }
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

         // need to put this one once the survey answer is already created
         if (SurveyAnswer::where('target_location_id', $id)->exists()) {

            return $this->responseUnprocessable('', 'Unable to Archive, Target location already in used!');
        }

        if (!$target_location->deleted_at) {

            $target_location->delete();

            return $this->responseSuccess('Target Location successfully archive', $target_location);
        }
    }

    protected function getBoundBox($location)
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Research System' // Replace with your app info
        ])->get("https://nominatim.openstreetmap.org/search", [
            'q' => $location,
            'format' => 'json',
            'polygon_geojson' => 1,
        ]);

        if ($response->successful() && !empty($response->json())) {
            $data = $response->json();
            return !empty($data[0]['boundingbox']) ? $data[0]['boundingbox'] : null;
        }

        return null; // Return null instead of the full response
    }



}
