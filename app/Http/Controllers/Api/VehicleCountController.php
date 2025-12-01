<?php

namespace App\Http\Controllers\Api;

use App\Exports\TrafficCounts\TrafficCountExport;
use App\Exports\VehicleCountExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MultipleSyncVehicleCountRequest;
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
            "total_left_motorcycle" => $request['total_left_motorcycle'],
            "total_left_tricycle" => $request['total_left_tricycle'],
            "total_left_bicycle" => $request['total_left_bicycle'],
            "total_left_e_bike" => $request['total_left_e_bike'],
            "total_left" =>  $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_motorcycle'] + $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
            "total_right_private_car" => $request['total_right_private_car'],
            "total_right_truck" => $request['total_right_truck'],
            "total_right_jeepney" => $request['total_right_jeepney'],
            "total_right_bus" => $request['total_right_bus'],
            "total_right_motorcycle" => $request['total_right_motorcycle'],
            "total_right_tricycle" => $request['total_right_tricycle'],
            "total_right_bicycle" => $request['total_right_bicycle'],
            "total_right_e_bike" => $request['total_right_e_bike'],
            "total_right" =>  $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_right_motorcycle'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'],
            "grand_total" =>  $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_left_motorcycle'] +  $request['total_right_motorcycle'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'] +
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
            "total_left_motorcycle" => $request['total_left_motorcycle'],
            "total_left_tricycle" => $request['total_left_tricycle'],
            "total_left_bicycle" => $request['total_left_bicycle'],
            "total_left_e_bike" => $request['total_left_e_bike'],
            "total_left" => $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_motorcycle'] +  $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
            "total_right_private_car" => $request['total_right_private_car'],
            "total_right_truck" => $request['total_right_truck'],
            "total_right_jeepney" => $request['total_right_jeepney'],
            "total_right_bus" => $request['total_right_bus'],
            "total_right_motorcycle" => $request['total_right_motorcycle'],
            "total_right_tricycle" => $request['total_right_tricycle'],
            "total_right_bicycle" => $request['total_right_bicycle'],
            "total_right_e_bike" => $request['total_right_e_bike'],
            "total_right" =>  $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_right_motorcycle'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'],
            "grand_total" =>  $request['total_right_private_car'] + $request['total_right_truck'] + $request['total_right_jeepney'] + $request['total_right_bus'] + $request['total_left_motorcycle'] +  $request['total_right_motorcycle'] + $request['total_right_tricycle'] + $request['total_right_bicycle'] + $request['total_right_e_bike'] +
                $request['total_left_private_car'] + $request['total_left_truck'] + $request['total_left_jeepney'] + $request['total_left_bus'] + $request['total_left_tricycle'] + $request['total_left_bicycle'] + $request['total_left_e_bike'],
        ]);

        return $this->responseSuccess('Vehicle Count successfully updated', $vehicle_count);
    }

    public function multiple_sync(MultipleSyncVehicleCountRequest $request)
    {
        $user = auth('sanctum')->user();
        $todayDate = Carbon::now()->format('Y-m-d H:i:s');
        $syncTime = Carbon::now();

        $vehicleCounts = $request->input('vehicle_counts');

        // Get all unique target location IDs
        $targetLocationIds = array_unique(array_column($vehicleCounts, 'target_location_id'));

        // Fetch all target locations at once
        $targetLocations = TargetLocation::whereIn('id', $targetLocationIds)->get()->keyBy('id');

        // Validate all target locations first
        $validationErrors = [];

        foreach ($vehicleCounts as $index => $countData) {
            $targetLocationId = $countData['target_location_id'];
            $targetLocation = $targetLocations->get($targetLocationId);

            if (!$targetLocation) {
                $validationErrors[] = [
                    'status' => 422,
                    'title' => 'Validation Error',
                    'detail' => 'Target location not found.',
                    'source' => [
                        'pointer' => "/vehicle_counts/{$index}"
                    ]
                ];
                continue;
            }

            // Finalization check
            if (!$targetLocation->is_final) {
                $validationErrors[] = [
                    'status' => 422,
                    'title' => 'Validation Error',
                    'detail' => 'Cannot insert vehicle counts: target location is not finalized. Please contact your supervisor or support.',
                    'source' => [
                        'pointer' => "/vehicle_counts/{$index}"
                    ]
                ];
                continue;
            }

            // Done checker
            if ($targetLocation->is_done == 1) {
                $validationErrors[] = [
                    'status' => 422,
                    'title' => 'Validation Error',
                    'detail' => 'Cannot insert vehicle counts: target location is already marked as done.',
                    'source' => [
                        'pointer' => "/vehicle_counts/{$index}"
                    ]
                ];
                continue;
            }

            // Date checker
            if ($targetLocation->start_date > $todayDate) {
                $validationErrors[] = [
                    'status' => 422,
                    'title' => 'Validation Error',
                    'detail' => 'Cannot insert vehicle counts: target location has not started yet.',
                    'source' => [
                        'pointer' => "/vehicle_counts/{$index}"
                    ]
                ];
                continue;
            }

            // Check for existing duplicate in database
            $exists = VehicleCount::where('target_location_id', $targetLocationId)
                ->where('date', $countData['date'])
                ->where('time_range', $countData['time_range'])
                ->where('time_period', $countData['time_period'])
                ->exists();

            if ($exists) {
                $validationErrors[] = [
                    'status' => 422,
                    'title' => 'Validation Error',
                    'detail' => 'This vehicle count entry already exists for the given date, time range, and location.',
                    'source' => [
                        'pointer' => "/vehicle_counts/{$index}"
                    ]
                ];
            }
        }

        // If there are any validation errors, return them all at once
        if (!empty($validationErrors)) {
            return response()->json([
                'errors' => $validationErrors
            ], 422);
        }

        // All validations passed, proceed with bulk insert
        $createdRecords = [];

        DB::beginTransaction();

        try {
            foreach ($vehicleCounts as $countData) {
                $totalLeft = $countData['total_left_private_car'] +
                    $countData['total_left_truck'] +
                    $countData['total_left_jeepney'] +
                    $countData['total_left_bus'] +
                    $countData['total_left_motorcycle'] +
                    $countData['total_left_tricycle'] +
                    $countData['total_left_bicycle'] +
                    $countData['total_left_e_bike'];

                $totalRight = $countData['total_right_private_car'] +
                    $countData['total_right_truck'] +
                    $countData['total_right_jeepney'] +
                    $countData['total_right_bus'] +
                    $countData['total_right_motorcycle'] +
                    $countData['total_right_tricycle'] +
                    $countData['total_right_bicycle'] +
                    $countData['total_right_e_bike'];

                $createdRecord = VehicleCount::create([
                    'target_location_id' => $countData['target_location_id'],
                    'date' => $countData['date'],
                    'time_range' => $countData['time_range'],
                    'time_period' => $countData['time_period'],
                    'total_left_private_car' => $countData['total_left_private_car'],
                    'total_left_truck' => $countData['total_left_truck'],
                    'total_left_jeepney' => $countData['total_left_jeepney'],
                    'total_left_bus' => $countData['total_left_bus'],
                    'total_left_motorcycle' => $countData['total_left_motorcycle'],
                    'total_left_tricycle' => $countData['total_left_tricycle'],
                    'total_left_bicycle' => $countData['total_left_bicycle'],
                    'total_left_e_bike' => $countData['total_left_e_bike'],
                    'total_left' => $totalLeft,
                    'total_right_private_car' => $countData['total_right_private_car'],
                    'total_right_truck' => $countData['total_right_truck'],
                    'total_right_jeepney' => $countData['total_right_jeepney'],
                    'total_right_bus' => $countData['total_right_bus'],
                    'total_right_motorcycle' => $countData['total_right_motorcycle'],
                    'total_right_tricycle' => $countData['total_right_tricycle'],
                    'total_right_bicycle' => $countData['total_right_bicycle'],
                    'total_right_e_bike' => $countData['total_right_e_bike'],
                    'total_right' => $totalRight,
                    'grand_total' => $totalLeft + $totalRight,
                    'surveyor_id' => $user->id,
                    'sync_at' => $syncTime,
                    'created_at' => $countData['created_at'],
                ]);

                $createdRecords[] = $createdRecord;
            }

            DB::commit();

            return $this->responseCreated(
                'Vehicle Counts Successfully Synced',
                [
                    'total_synced' => count($createdRecords),
                    'records' => $createdRecords
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'errors' => [
                    [
                        'status' => 500,
                        'title' => 'Server Error',
                        'detail' => 'Failed to sync vehicle counts: ' . $e->getMessage()
                    ]
                ]
            ], 500);
        }
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

        // Parse and normalize dates
        $start_date = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : null;

        $end_date = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : null;

        return Excel::download(
            new TrafficCountExport(
                $target_locations,
                $surveyor_id,
                $start_date,
                $end_date
            ),
            'Vehicle Counts.xlsx'
        );
    }
}
