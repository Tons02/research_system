<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LocationRequest;
use Essa\APIToolKit\Api\ApiResponse;
use App\Models\Location;

class LocationController extends Controller
{
    use ApiResponse;
    public function index(Request $request){
        $status = $request->query('status');
        
        $Locations = Location::
        with('sub_unit')
        ->when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
        ->orderBy('created_at', 'desc')
        ->useFilters()
        ->dynamicPaginate();
        
        return $this->responseSuccess('Locations display successfully', $Locations );
    }

    public function store(LocationRequest $request)
    {
        $locationsData = $request->input('locations');
    
        // Prepare data for upsert (exclude 'sub_units')
        $locationsForUpsert = array_map(function ($location) {
            return [
                'sync_id' => $location['sync_id'],
                'location_code' => $location['location_code'],
                'location_name' => $location['location_name'],
                'updated_at' => $location['updated_at'],
                'deleted_at' => $location['deleted_at']
            ];
        }, $locationsData);
    
        // Perform the upsert for locations
        Location::upsert(
            $locationsForUpsert,
            ['sync_id'], // Unique key
            ['location_code', 'location_name', 'updated_at', 'deleted_at'] // Columns to update
        );
    
        // Handle relationships (sub_units)
        foreach ($locationsData as $locationData) {
            $location = Location::where('sync_id', $locationData['sync_id'])->first();
    
            if ($location && isset($locationData['sub_units'])) {
                // Sync the sub_units relationship
                $location->sub_unit()->sync($locationData['sub_units']);
            }
        }
    
        return $this->responseSuccess('Sync location unit successfully');
    }
     
}
