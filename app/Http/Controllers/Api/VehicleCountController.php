<?php

namespace App\Http\Controllers\Api;

use App\Exports\TrafficCounts\TrafficCountExport;
use App\Exports\VehicleCountExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleCountExportRequest;
use App\Http\Requests\VehicleCountRequest;
use App\Http\Resources\VehicleCountResource;
use App\Models\TargetLocation;
use App\Models\VehicleCount;
use Carbon\Carbon;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VehicleCountController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $status = $request->query('status');
        $target_location_id = $request->query('target_location_id');
        $pagination = $request->query('pagination');

        $VehicleCount = VehicleCount::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->whereHas('target_locations', function ($query) use ($target_location_id) {
                $query->where('target_location_id', $target_location_id);
            })
            ->orderBy('date', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            VehicleCountResource::collection($VehicleCount);
        } else {
            $VehicleCount = VehicleCountResource::collection($VehicleCount);
        }
        return $this->responseSuccess('Foot Count display successfully', $VehicleCount);
    }

    public function store(VehicleCountRequest $request)
    {
        $user = auth('sanctum')->user();

        $target_location = TargetLocation::find($request->target_location_id);

        // check if the user is tag on the target location vehicle count
        if ($target_location->vehicle_counted_by_user_id != $user->id) {
            return $this->responseUnprocessable('', 'You cannot insert vehicle counts because it is not tagged on your account. Please contact your supervisor or support.');
        }

        // it can insert within the day
        if (
            $target_location->is_done == 1 &&
            $request->date > $target_location->updated_at
        ) {
            return $this->responseUnprocessable('', 'You cannot insert vehicle counts because it is already done.');
        }

        $create_vehicle_count = VehicleCount::create([
            "date" => $request->date,
            "time" => $request->time,
            "time_period" => Carbon::parse($request->time)->format('A'),
            "total_left" => $request->total_left,
            "total_right" => $request->total_right,
            "grand_total" => $request->total_left +  $request->total_right,
            "surveyor_id" => $user->id,
        ]);

        if ($request->target_location_id) {
            $create_vehicle_count->target_locations()->attach($request->target_location_id);
        }


        return $this->responseCreated('Vehicle Count Successfully Created', $create_vehicle_count);
    }

    public function update(VehicleCountRequest $request, $id)
    {
        $vehicle_count = VehicleCount::find($id);

        if (!$vehicle_count) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $vehicle_count->total_left = $request['total_left'];
        $vehicle_count->total_right = $request['total_right'];
        $vehicle_count->grand_total = $request['total_left'] + $request['total_right'];

        if (!$vehicle_count->isDirty()) {
            return $this->responseSuccess('No Changes', $vehicle_count);
        }

        $vehicle_count->save();

        return $this->responseSuccess('Vehicle Count successfully updated', $vehicle_count);
    }

    public function archived(Request $request, $id)
    {
        $vehicle_count = VehicleCount::withTrashed()->find($id);

        if (!$vehicle_count) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }

        if ($vehicle_count->deleted_at) {

            $vehicle_count->restore();
            return $this->responseSuccess('Vehicle Count successfully restore', $vehicle_count);
        }

        if (!$vehicle_count->deleted_at) {

            $vehicle_count->delete();
            return $this->responseSuccess('Vehicle Count successfully archive', $vehicle_count);
        }
    }

    public function export(VehicleCountExportRequest $request)
    {
        $target_locations = $request->query('target_location_id');

        return Excel::download(new TrafficCountExport($target_locations), 'Vehicle Counts.xlsx');
    }
}
