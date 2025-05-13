<?php

namespace App\Http\Controllers\Api;

use App\Exports\TrafficCounts\TrafficFootCountExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\FootCountRequest;
use App\Http\Requests\VehicleCountExportRequest;
use App\Http\Resources\FootCountResource;
use App\Models\FootCount;
use App\Models\TargetLocation;
use Carbon\Carbon;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FootCountController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {

        $status = $request->query('status');
        $target_location_id = $request->query('target_location_id');
        $pagination = $request->query('pagination');

        $FootCount = FootCount::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->whereHas('target_locations', function ($query) use ($target_location_id) {
                $query->where('target_location_id', $target_location_id);
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

        $target_location = TargetLocation::find($request->target_location_id);

        // check if the user is tag on the target location foot count
        if ($target_location->foot_counted_by_user_id != $user->id) {
            return $this->responseUnprocessable('', 'You cannot insert foot counts because it is not tagged on your account. Please contact your supervisor or support.');
        }

        // it can insert within the day
        if (
            $target_location->is_done == 1 &&
            $request->date > $target_location->updated_at
        ) {
            return $this->responseUnprocessable('', 'You cannot insert foot counts because it is already done.');
        }

        $create_foot_count = FootCount::create([
            "date" => $request->date,
            "time" => $request->time,
            "time_period" => Carbon::parse($request->time)->format('A'),
            "total_male" => $request->total_male,
            "total_female" => $request->total_female,
            "grand_total" => $request->total_male +  $request->total_female,
            "surveyor_id" => $user->id,
        ]);

        if ($request->target_location_id) {
            $create_foot_count->target_locations()->attach($request->target_location_id);
        }

        return $this->responseCreated('Foot Count Successfully Created', $create_foot_count);
    }

    public function update(FootCountRequest $request, $id)
    {
        $foot_count = FootCount::find($id);

        if (!$foot_count) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
        }

        $foot_count->total_male = $request['total_male'];
        $foot_count->total_female = $request['total_female'];
        $foot_count->grand_total = $request['total_male'] + $request['total_female'];

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
        $target_location_id = $request->query('target_location_id', null);

        return Excel::download(new TrafficFootCountExport($target_location_id), 'Foot Counts.xlsx');
    }
}
