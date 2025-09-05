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
            ->orderBy('date', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            VehicleCountResource::collection($VehicleCount);
        } else {
            $VehicleCount = VehicleCountResource::collection($VehicleCount);
        }
        return $this->responseSuccess('Vehicle Count display successfully', $VehicleCount);
    }

    public function store(VehicleCountRequest $request)
    {
        $user = auth('sanctum')->user();

        $TodayDate = Carbon::now()->format('Y-m-d H:i:s');

        $target_location = TargetLocation::find($request["target_location_id"]);

        // Finalization check
        if (!$target_location->is_final) {
            return $this->responseUnprocessable('', 'You cannot insert vehicle counts because it is not finalized. Please contact your supervisor or support.');
        }

        //done checker
        if ($target_location->is_done == 1) {
            return $this->responseUnprocessable('', 'You cannot insert vehicle counts because it is already marked as done.');
        }

        //date checker
        if ($target_location->start_date > $TodayDate) {
            return $this->responseUnprocessable('', 'You cannot insert vehicle counts because it is not started yet.');
        }


        $create_vehicle_count = VehicleCount::create([
            "target_location_id" => $request['target_location_id'],
            "date" => $request['date'],
            "time_range" => $request['time_range'],
            "time_period" => $request['time_period'],
            "total_left_private_car" => $request['total_left_private_car'],
            "total_left_truck" => $request['total_left_truck'],
            "total_left_jeepney" => $request['total_left_jeepney'],
            "total_left_bus" => $request['total_left_bus'],
            "total_left_tricycle" => $request['total_left_tricycle'],
            "total_left_bicycle" => $request['total_left_bicycle'],
            "total_left_e_bike" => $request['total_left_e_bike'],
            "total_left" =>  $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
            "total_right_private_car" => $request['total_right_private_car'],
            "total_right_truck" => $request['total_right_truck'],
            "total_right_jeepney" => $request['total_right_jeepney'],
            "total_right_bus" => $request['total_right_bus'],
            "total_right_tricycle" => $request['total_right_tricycle'],
            "total_right_bicycle" => $request['total_right_bicycle'],
            "total_right_e_bike" => $request['total_right_e_bike'],
            "total_right" =>  $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'],
            "grand_total" =>  $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'] +
                $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
            "surveyor_id" => $user->id,
            "sync_at" => Carbon::now(),
            "created_at" => $request['created_at'],
        ]);


        return $this->responseCreated('Vehicle Count Successfully Created', $create_vehicle_count);
    }

    public function update(VehicleCountRequest $request, $id)
    {
        $vehicle_count = VehicleCount::find($id);

        if (!$vehicle_count) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $vehicle_count->update([
            "date" => $request['date'],
            "time_range" => $request['time_range'],
            "time_period" => $request['time_period'],
            "total_left_private_car" => $request['total_left_private_car'],
            "total_left_truck" => $request['total_left_truck'],
            "total_left_jeepney" => $request['total_left_jeepney'],
            "total_left_bus" => $request['total_left_bus'],
            "total_left_tricycle" => $request['total_left_tricycle'],
            "total_left_bicycle" => $request['total_left_bicycle'],
            "total_left_e_bike" => $request['total_left_e_bike'],
            "total_left" => $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
            "total_right_private_car" => $request['total_right_private_car'],
            "total_right_truck" => $request['total_right_truck'],
            "total_right_jeepney" => $request['total_right_jeepney'],
            "total_right_bus" => $request['total_right_bus'],
            "total_right_tricycle" => $request['total_right_tricycle'],
            "total_right_bicycle" => $request['total_right_bicycle'],
            "total_right_e_bike" => $request['total_right_e_bike'],
            "total_right" => $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'],
            "grand_total" =>
            $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'] +
                $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
        ]);

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
        $surveyor_id = $request->query('surveyor_id');

        return Excel::download(new TrafficCountExport($target_locations, $surveyor_id), 'Vehicle Counts.xlsx');
    }
}
