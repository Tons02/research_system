<?php

namespace App\Http\Controllers\Api;

use App\Exports\TrafficCounts\TrafficFootCountExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\FootCountRequest;
use App\Http\Requests\MultipleSyncFootCountRequest;
use App\Http\Requests\VehicleCountExportRequest;
use App\Http\Resources\FootCountResource;
use App\Models\FootCount;
use App\Models\TargetLocation;
use Carbon\Carbon;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FootCountController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {

        $status = $request->query('status');
        $pagination = $request->query('pagination');

        $FootCount = FootCount::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->orderBy('date', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            FootCountResource::collection($FootCount);
        } else {
            $FootCount = FootCountResource::collection($FootCount);
        }
        return $this->responseSuccess('Foot Count display successfully', $FootCount);
    }

    public function store(FootCountRequest $request)
    {
        $user = auth('sanctum')->user();

        $TodayDate = Carbon::now()->format('Y-m-d H:i:s');

        $target_location = TargetLocation::find($request["target_location_id"]);

        // Finalization check
        if (!$target_location->is_final) {
            return $this->responseUnprocessable('', 'You cannot insert foot counts because it is not finalized. Please contact your supervisor or support.');
        }

        //done checker
        if ($target_location->is_done == 1) {
            return $this->responseUnprocessable('', 'You cannot insert foot counts because it is already marked as done.');
        }

        //date checker
        if ($target_location->start_date > $TodayDate) {
            return $this->responseUnprocessable('', 'You cannot insert foot counts because it is not started yet.');
        }

        $create_foot_count = FootCount::create([
            "target_location_id" => $request['target_location_id'],
            "date" => $request['date'],
            "time_range" => $request['time_range'],
            "time_period" => $request['time_period'],
            "total_left_male" => $request['total_left_male'],
            "total_right_male" => $request['total_left_male'],
            "total_male" => $request['total_left_male'] + $request['total_right_male'],
            "total_left_female" => $request['total_left_male'],
            "total_right_female" => $request['total_left_female'],
            "total_female" => $request['total_left_female'] + $request['total_right_female'],
            "grand_total" => $request['total_left_male'] + $request['total_right_male'] + $request['total_left_female'] + $request['total_right_female'],
            "surveyor_id" => $user->id,
            "sync_at" => Carbon::now(),
            "created_at" => $request['created_at'],
        ]);
        return $this->responseCreated('Foot Count Successfully Created', $create_foot_count);
    }

    public function multiple_sync(MultipleSyncFootCountRequest $request)
    {
        $user = auth('sanctum')->user();
        $todayDate = Carbon::now()->format('Y-m-d H:i:s');
        $syncTime = Carbon::now();

        $footCounts = $request->input('foot_counts');

        // Get all unique target location IDs
        $targetLocationIds = array_unique(array_column($footCounts, 'target_location_id'));

        // Fetch all target locations at once
        $targetLocations = TargetLocation::whereIn('id', $targetLocationIds)->get()->keyBy('id');

        // Validate all target locations first
        $validationErrors = [];

        foreach ($footCounts as $index => $countData) {
            $targetLocationId = $countData['target_location_id'];
            $targetLocation = $targetLocations->get($targetLocationId);

            if (!$targetLocation) {
                $validationErrors[] = [
                    'index' => $index,
                    'target_location_id' => $targetLocationId,
                    'message' => "Target location not found."
                ];
                continue;
            }

            // Finalization check
            if (!$targetLocation->is_final) {
                $validationErrors[] = [
                    'index' => $index,
                    'target_location_id' => $targetLocationId,
                    'message' => "Cannot insert foot counts for entry #{$index}: target location is not finalized. Please contact your supervisor or support."
                ];
                continue;
            }

            // Done checker
            if ($targetLocation->is_done == 1) {
                $validationErrors[] = [
                    'index' => $index,
                    'target_location_id' => $targetLocationId,
                    'message' => "Cannot insert foot counts for entry #{$index}: target location is already marked as done."
                ];
                continue;
            }

            // Date checker
            if ($targetLocation->start_date > $todayDate) {
                $validationErrors[] = [
                    'index' => $index,
                    'target_location_id' => $targetLocationId,
                    'message' => "Cannot insert foot counts for entry #{$index}: target location has not started yet."
                ];
                continue;
            }

            // Check for existing duplicate in database
            $exists = FootCount::where('target_location_id', $targetLocationId)
                ->where('date', $countData['date'])
                ->where('time_range', $countData['time_range'])
                ->where('time_period', $countData['time_period'])
                ->exists();

            if ($exists) {
                $validationErrors[] = [
                    'index' => $index,
                    'target_location_id' => $targetLocationId,
                    'message' => "Foot count already exists for this date, time range, and time period."
                ];
            }
        }

        // If there are any validation errors, return them all at once
        if (!empty($validationErrors)) {
            return response()->json([
                'message' => 'Validation failed for one or more foot counts',
                'error' => 'Validation Error',
                'errors' => $validationErrors,
                'total_errors' => count($validationErrors),
                'created_count' => 0
            ], 400);
        }

        // All validations passed, proceed with bulk insert
        $createdRecords = [];

        DB::beginTransaction();

        try {
            foreach ($footCounts as $countData) {
                $totalMale = $countData['total_left_male'] + $countData['total_right_male'];
                $totalFemale = $countData['total_left_female'] + $countData['total_right_female'];

                $createdRecord = FootCount::create([
                    'target_location_id' => $countData['target_location_id'],
                    'date' => $countData['date'],
                    'time_range' => $countData['time_range'],
                    'time_period' => $countData['time_period'],
                    'total_left_male' => $countData['total_left_male'],
                    'total_right_male' => $countData['total_right_male'],
                    'total_male' => $totalMale,
                    'total_left_female' => $countData['total_left_female'],
                    'total_right_female' => $countData['total_right_female'],
                    'total_female' => $totalFemale,
                    'grand_total' => $totalMale + $totalFemale,
                    'surveyor_id' => $user->id,
                    'sync_at' => $syncTime,
                    'created_at' => $countData['created_at'],
                ]);

                $createdRecords[] = $createdRecord;
            }

            DB::commit();

            return $this->responseCreated(
                'Foot Counts Successfully Synced',
                [
                    'total_synced' => count($createdRecords),
                    'created_count' => count($createdRecords),
                    'error_count' => 0,
                    'records' => $createdRecords
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to sync foot counts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(FootCountRequest $request, $id)
    {
        $foot_count = FootCount::find($id);

        if (!$foot_count) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $target_location = TargetLocation::find($foot_count->target_location_id);

        // Finalization check
        if (!$target_location->is_final) {
            return $this->responseUnprocessable('', 'You cannot insert foot count because it is not finalized. Please contact your supervisor or support.');
        }

        //done checker
        if ($target_location->is_done == 1) {
            return $this->responseUnprocessable('', 'You cannot insert foot count because it is already marked as done.');
        }

        $foot_count->date = $request['date'];
        $foot_count->time_range = $request['time_range'];
        $foot_count->time_period = $request['time_period'];
        $foot_count->total_left_male = $request['total_left_male'];
        $foot_count->total_right_male = $request['total_right_male'];
        $foot_count->total_male = $request['total_left_male'] + $request['total_right_male'];
        $foot_count->total_left_female = $request['total_left_female'];
        $foot_count->total_right_female = $request['total_right_female'];
        $foot_count->total_female = $request['total_left_female'] + $request['total_right_female'];
        $foot_count->grand_total = $request['total_left_male'] + $request['total_right_male'] + $request['total_left_female'] + $request['total_right_female'];

        if (!$foot_count->isDirty()) {
            return $this->responseSuccess('No Changes', $foot_count);
        }

        $foot_count->save();

        return $this->responseSuccess('Foot Count successfully updated', $foot_count);
    }

    public function archived(Request $request, $id)
    {
        $foot_count = FootCount::withTrashed()->find($id);

        if (!$foot_count) {
            return $this->responseUnprocessable('', 'Invalid id please check the id and try again.');
        }

        if ($foot_count->deleted_at) {

            $foot_count->restore();
            return $this->responseSuccess('Foot Count successfully restore', $foot_count);
        }

        if (!$foot_count->deleted_at) {

            $foot_count->delete();
            return $this->responseSuccess('Foot Count successfully archive', $foot_count);
        }
    }

    public function export(VehicleCountExportRequest $request)
    {
        $target_location_id = $request->query('target_location_id');
        $surveyor_id = $request->query('surveyor_id');

        // ==== SAFE & CLEAN DATE PARSING HERE ====
        $start_date = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : null;

        $end_date = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : null;

        return Excel::download(
            new TrafficFootCountExport(
                $target_location_id,
                $surveyor_id,
                $start_date,
                $end_date
            ),
            'Foot Counts.xlsx'
        );
    }
}
