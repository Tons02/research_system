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
use Illuminate\Support\Facades\Http;

class TargetLocationController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');


        $TargetLocation = TargetLocation::when($status === "inactive", function ($query) {
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

        $target_location_id->region_psgc_id = $request['region_psgc_id'];
        $target_location_id->region = $request['region'];
        $target_location_id->province_psgc_id = $request['province_psgc_id'];
        $target_location_id->province = $request['province'];
        $target_location_id->city_municipality_psgc_id = $request['city_municipality_psgc_id'];
        $target_location_id->city_municipality = $request['city_municipality'];
        $target_location_id->sub_municipality_psgc_id = $request['sub_municipality_psgc_id'];
        $target_location_id->sub_municipality = $request['sub_municipality'];
        $target_location_id->barangay_psgc_id = $request['barangay_psgc_id'];
        $target_location_id->barangay = $request['barangay'];
        $target_location_id->street = $request['street'];
        $target_location_id->response_limit = $request['response_limit'];
        $target_location_id->form_id = $request['form_id'];
        $target_location_id->bound_box = $this->getBoundBox(implode(', ', array_filter([
            $request["barangay"],
            $request["city_municipality"],
            $request["province"],
            $request["region"],
            'Philippines'
        ]))) ?? [];

        if (!$target_location_id->isDirty()) {
            return $this->responseSuccess('No Changes', $target_location_id);
        }

        $target_location_id->save();

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
