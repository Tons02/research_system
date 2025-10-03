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
use App\Models\TargetLocationUsers;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TargetLocationController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');
        $user = auth('sanctum')->user();
        $userId = $user->id;
        $role = strtolower($user->role->name);


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

    public function show(Request $request, $id)
    {
        $TargetLocation = TargetLocation::where('id', $id)->first();

        if (!$TargetLocation) {
            return $this->responseUnprocessable('', 'Invalid ID');
        }

        return $this->responseSuccess('Single Target Location display successfully', $TargetLocation);
    }

    public function store(TargetLocationRequest $request)
    {
        DB::beginTransaction();

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
                "title" => $request["title"],
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
                "mobile_locations" => $request["mobile_locations"],
                "bound_box" => $this->getBoundBox(implode(', ', array_filter([
                    $request["barangay"],
                    $request["city_municipality"],
                    $request["province"],
                    $request["region"],
                    'Philippines'
                ]))) ?? [],
                "response_limit" => $request["response_limit"],
                "form_id" => $request["form_id"],
                "form_history_id" => $create_form_history->id,
                "is_done" => 0,
                "is_final" => 0,
            ]);

            DB::commit();
            return $this->responseCreated('Target Location Successfully Created', $create_target_location);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if an error occurs
            return $this->responseServerError('Network Error Please Try Again');
        }
    }


    public function update(TargetLocationRequest $request, $id)
    {
        DB::beginTransaction(); // Start the transaction

        try {

            $target_location = TargetLocation::find($id);

            if (!$target_location) {
                return $this->responseUnprocessable('', 'Invalid ID provided for updating. Please check the ID and try again.');
            }

            // Block if finalized or if the start date is in the past

            if ($target_location->is_final && Carbon::now()->gt(Carbon::parse($target_location->start_date))) {

                return $this->responseUnprocessable('', 'This target location is finalized or has already started and can no longer be edited.');
            }

            $form = Form::find($request['form_id']);

            $form_history = FormHistories::find($request['form_history_id']);

            $form_history->update([
                'title' => $form->title,
                'description' => $form->description,
                'sections' => $form->sections,
            ]);


            $formHistoryUpdated = $form_history->wasChanged();

            $target_location->title = $request['title'];
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
            $target_location->mobile_locations = $request['mobile_locations'];
            $target_location->form_id = $request['form_id'];
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

            // If there were no changes at all
            if (!$targetLocationUpdated  && !$formHistoryUpdated) {
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

        if (User::where('id', $target_location->vehicle_counted_by_user_id)
            ->onlyTrashed()
            ->exists()
        ) {
            return $this->responseUnprocessable('', 'Unable to restore. The user tagged on vehicle count is archived');
        }

        if (User::where('id', $target_location->foot_counted_by_user_id)
            ->onlyTrashed()
            ->exists()
        ) {
            return $this->responseUnprocessable('', 'Unable to restore. The user tagged on foot count is archived');
        }

        $userIds = TargetLocationUsers::withTrashed()
            ->where('target_location_id', $target_location->id)
            ->pluck('user_id');

        // Fetch soft-deleted users from the Users model
        $archivedUsers = User::onlyTrashed()
            ->whereIn('id', $userIds)
            ->get(['first_name', 'last_name']);

        if ($archivedUsers->isNotEmpty()) {
            $names = $archivedUsers
                ->map(fn($user) => "{$user->first_name} {$user->last_name}")
                ->implode(', ');

            return $this->responseUnprocessable('', "Unable to restore. The following user(s) tagged as surveyor are archived: {$names}");
        }

        if ($target_location->deleted_at) {

            if (TargetLocation::orwhere('vehicle_counted_by_user_id', $id)
                ->where('is_done', 0)
                ->orwhere('foot_counted_by_user_id', $id)
                ->exists()
            ) {
                return $this->responseUnprocessable('', 'Unable to restore. Some users are already assigned to another active target location.');
            }

            // Get all users associated (including soft-deleted pivot records)
            $userIds = TargetLocationUsers::withTrashed()
                ->where('target_location_id', $target_location->id)
                ->pluck('user_id');

            // Check if any of these users are already assigned to another target location (not this one),
            // where is_done = 0 and not soft-deleted
            $conflict = TargetLocationUsers::whereIn('user_id', $userIds)
                ->where('target_location_id', '!=', $target_location->id)
                ->where('is_done', 0)
                ->whereNull('deleted_at')
                ->exists();

            if ($conflict) {
                return $this->responseUnprocessable('', 'Unable to restore. Some users are already assigned to another active target location.');
            }

            $target_location->restore();

            // Restore pivot records
            foreach ($target_location->target_locations_users()->withTrashed()->get() as $user) {
                if ($user->pivot->trashed()) {
                    $user->pivot->restore();
                }
            }

            return $this->responseSuccess('Target Location successfully restored', $target_location);
        }


        // need to put this one once the survey answer is already created
        if (SurveyAnswer::where('target_location_id', $id)->exists()) {

            return $this->responseUnprocessable('', 'Unable to Archive, Target location already in used!');
        }

        if (!$target_location->deleted_at) {

            DB::beginTransaction();

            try {

                // $target_location->target_locations_users()->detach(); // instead of  detach i want to use softdelete so that if i want to restore them i can just remove the soft delte
                $target_location->delete();

                foreach ($target_location->target_locations_users as $user) {
                    $user->pivot->delete(); // soft deletes the pivot
                }

                DB::commit();
                return $this->responseSuccess('Target Location successfully archived', $target_location);
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->responseServerError('Network Error Please Try Again');
            }
        }
    }

    public function finalized(TargetLocationRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $target_location = TargetLocation::where('id', $id)->first();

            $addOneDay = Carbon::now()->addDay();
            $allowanceOneDay = $addOneDay->format('Y-m-d H:i:s');

            if (!$target_location) {
                return $this->responseUnprocessable('', 'Invalid ID provided for finalizing. Please check the ID and try again.');
            }

            if ($target_location->is_final == 1) {
                return $this->responseUnprocessable('', 'The survey on this target location is already started');
            }

            $form = Form::find($target_location->form_id);

            $form_history = FormHistories::find($target_location->form_history_id);

            $form_history->update([
                'title' => $form->title,
                'description' => $form->description,
                'sections' => $form->sections,
            ]);

            $target_location->update([
                'is_final' => $request["is_final"],
                'start_date' => $allowanceOneDay,
            ]);

            DB::commit();
            return $this->responseSuccess('Target Location successfully finalized', $target_location);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseServerError('Network Error Please Try Again');
        }
    }


    public function endSurvey(TargetLocationRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $target_location = TargetLocation::where('id', $id)->first();
            $today = Carbon::now();
            $TodayDate = $today->format('Y-m-d H:i:s');

            if (!$target_location) {
                return $this->responseUnprocessable('', 'Invalid ID provided for ending survey. Please check the ID and try again.');
            }

            if ($target_location->is_done === true) {
                return $this->responseUnprocessable('', 'The target location has already ended.');
            }

            $target_location->update([
                'is_done' => $request["is_done"],
                'end_date' => $TodayDate,
            ]);

            DB::commit();
            return $this->responseSuccess('Target Location successfully ended', $target_location);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseServerError('Network Error Please Try Again');
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

    public function skipCountdown(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $target_location = TargetLocation::where('id', $id)
                ->where('is_final', '1')
                ->first();
            $today = Carbon::now();
            $TodayDate = $today->format('Y-m-d H:i:s');

            if (!$target_location) {
                return $this->responseUnprocessable('', 'Invalid ID provided for skip countdown. Please check the ID and try again.');
            }

            if ($TodayDate >= $target_location->start_date) {
                return $this->responseUnprocessable('', 'The survey countdown for this location has already started.');
            }

            $form = Form::find($target_location->form_id);

            $form_history = FormHistories::find($target_location->form_history_id);

            $form_history->update([
                'title' => $form->title,
                'description' => $form->description,
                'sections' => $form->sections,
            ]);

            $target_location->update([
                'form_id' => null,
                'start_date' => $TodayDate,
            ]);

            DB::commit();
            return $this->responseSuccess('The survey on this location has successfully started.', $target_location);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseServerError('Network Error Please Try Again');
        }
    }

    public function target_location_users(Request $request)
    {
        $target_location_id = $request->query('target_location_id');

        // Fetch users who participated in survey for this target location
        $target_location_users_for_survey = SurveyAnswer::with('surveyor')
            ->where('target_location_id', $target_location_id)
            ->get()
            ->pluck('surveyor')
            ->unique('id')
            ->values();

        return $this->responseSuccess(
            'Users for specific Target Location displayed successfully',
            $target_location_users_for_survey
        );
    }
}
