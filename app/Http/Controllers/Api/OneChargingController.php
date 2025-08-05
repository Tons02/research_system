<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OneCharging;
use Carbon\Carbon;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class OneChargingController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $status = $request->query('status');

        $OneCharging = OneCharging::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        return $this->responseSuccess('One Charging display successfully', $OneCharging);
    }

    public function store(Request $request)
    {
        $apiKey = env('ONE_CHARGING_API_KEY');
        $apiEndpoint = env('ONE_CHARGING_GET');

        if (!$apiKey || !$apiEndpoint) {
            return $this->responseBadRequest('API token or endpoint not configured');
        }

        // Send request to external API
        $response = Http::withHeaders([
            'API_KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->get($apiEndpoint);

        if (!$response->successful()) {
            return $this->responseServerError('Failed to fetch data from One Charging API');
        }

        $apiData = $response->json()['data'];

        // Process and validate
        $processedData = collect($apiData)->map(function ($item) {
            $item['sync_id'] = $item['id'];

            $item['created_at'] = isset($item['created_at']) ? Carbon::parse($item['created_at'])->format('Y-m-d H:i:s') : null;
            $item['updated_at'] = isset($item['updated_at']) ? Carbon::parse($item['updated_at'])->format('Y-m-d H:i:s') : null;
            $item['deleted_at'] = isset($item['deleted_at']) && $item['deleted_at'] !== null
                ? Carbon::parse($item['deleted_at'])->format('Y-m-d H:i:s')
                : null;

            return $item;
        });

        // Validation rules
        $rules = [
            'sync_id' => 'required',
            'code' => 'required',
            'name' => 'required',
            'company_code' => 'required',
            'company_name' => 'required',
            'business_unit_code' => 'required',
            'business_unit_name' => 'required',
            'department_code' => 'required',
            'department_name' => 'required',
            'unit_code' => 'required',
            'unit_name' => 'required',
            'sub_unit_code' => 'required',
            'sub_unit_name' => 'required',
            'location_code' => 'required',
            'location_name' => 'required',
            'created_at' => 'required|date',
            'updated_at' => 'required|date',
            'deleted_at' => 'nullable|date',
        ];

        // Filter valid records
        $validData = $processedData->filter(function ($item) use ($rules) {
            $validator = Validator::make($item, $rules);
            if ($validator->fails()) {
                // Optionally log invalid data
                // Log::warning('Invalid OneCharging record skipped', [
                //     'data' => $item,
                //     'errors' => $validator->errors()->all(),
                // ]);
                return $this->responseUnprocessable('', 'Data has been synchronized successfully.');
            }
            return true;
        })->values()->toArray();

        // Prevent updating soft-deleted records that are in use by users
        $validData = collect($validData)->filter(function ($item) {
            if ($item['deleted_at'] !== null) {
                // Check if any user is using this sync_id
                $isUsed = DB::table('users')
                    ->where('one_charging_sync_id', $item['sync_id'])
                    ->exists();

                // Skip if in use
                return !$isUsed;
            }

            return true;
        })->values()->toArray();


        // Insert or update valid data
        OneCharging::upsert(
            $validData,
            ['sync_id'],
            [
                'code',
                'name',
                'company_code',
                'company_name',
                'business_unit_code',
                'business_unit_name',
                'department_code',
                'department_name',
                'unit_code',
                'unit_name',
                'sub_unit_code',
                'sub_unit_name',
                'location_code',
                'location_name',
                'created_at',
                'updated_at',
                'deleted_at'
            ]
        );

        return $this->responseSuccess('Data has been synchronized successfully.');
    }


    public function sync_from_one_rdf(Request $request)
    {
        $rawData = $request->all();

        // $sync = collect($rawData)->map(function ($item) {
        //     $item['unit_id'] = $item['department_unit_id'];
        //     $item['unit_code'] = $item['department_unit_code'];
        //     $item['unit_name'] = $item['department_unit_name'];

        //     unset(
        //         $item['department_unit_id'],
        //         $item['department_unit_code'],
        //         $item['department_unit_name']
        //     );

        //     return $item;
        // })->toArray();

        $sync = collect($request->all())->map(function ($item) {
            return [
                ...$item,
                'unit_id' => $item['department_unit_id'],
                'unit_code' => $item['department_unit_code'],
                'unit_name' => $item['department_unit_name'],
            ];
        })->map(function ($item) {
            unset($item['department_unit_id'], $item['department_unit_code'], $item['department_unit_name']);
            return $item;
        })->toArray();

        $charging = OneCharging::upsert(
            $sync,
            ["sync_id"],
            [
                "code",
                "name",
                "company_id",
                "company_code",
                "company_name",
                "business_unit_id",
                "business_unit_code",
                "business_unit_name",
                "department_id",
                "department_code",
                "department_name",
                "unit_id",              // now mapped from department_unit_id
                "unit_code",            // now mapped from department_unit_code
                "unit_name",            // now mapped from department_unit_name
                "sub_unit_id",
                "sub_unit_code",
                "sub_unit_name",
                "location_id",
                "location_code",
                "location_name",
                "deleted_at",
            ]
        );

        return $this->responseSuccess($charging, 'Data has been synchronized successfully.');
    }
}
