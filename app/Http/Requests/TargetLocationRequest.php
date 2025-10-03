<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TargetLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "title" => [
                "required",
                "sometimes",
                $this->route()->target_location
                    ? "unique:target_locations,title," . $this->route()->target_location
                    : "unique:target_locations,title",
            ],
            "region_psgc_id" => [
                "required",
                "sometimes"
            ],
            "region" => [
                "required",
                "sometimes"
            ],
            "city_municipality_psgc_id" => [
                "required",
                "sometimes"
            ],
            "city_municipality" => [
                "required",
                "sometimes"
            ],
            "barangay" => [
                "required",
                "sometimes"
            ],
            "street" => [
                "required",
                "sometimes"
            ],
            'mobile_locations' => ['required', 'sometimes', 'array', 'min:1'],

            // Region & Province rules
            'mobile_locations.*.region_name' => [
                'nullable',
                'required'
            ],
            'mobile_locations.*.region_psgc_id' => [
                'nullable',
                'required'
            ],
            'mobile_locations.*.province_name' => [
                'nullable',
            ],
            'mobile_locations.*.province_psgc_id' => [
                'nullable',
            ],

            // City municipalities
            'mobile_locations.*.city_municipalities' => ['required', 'array', 'min:1'],
            'mobile_locations.*.city_municipalities.*.city_municipality_psgc_id' => ['required', 'string'],
            'mobile_locations.*.city_municipalities.*.city_municipality' => ['required', 'string'],

            // Sub Municipalities
            'mobile_locations.*.city_municipalities.*.sub_municipalities' => ['array', 'nullable'],
            'mobile_locations.*.city_municipalities.*.sub_municipalities.*.sub_municipalities_psgc_id' => ['required', 'sometimes', 'string'],
            'mobile_locations.*.city_municipalities.*.sub_municipalities.*.sub_municipalities' => ['required', 'sometimes', 'string'],

            // Sub Municipalities Barangays
            'mobile_locations.*.city_municipalities.*.sub_municipalities.*.barangays' => ['array', 'nullable'],
            'mobile_locations.*.city_municipalities.*.sub_municipalities.*.barangays.*.barangay_psgc_id' => ['required', 'sometimes', 'string'],
            'mobile_locations.*.city_municipalities.*.sub_municipalities.*.barangays.*.barangay' => ['required', 'sometimes', 'string'],

            // Barangays
            'mobile_locations.*.city_municipalities.*.barangays' => ['array'],

            'mobile_locations.*.city_municipalities.*.barangays.*.barangay_psgc_id' => ['required', 'string'],
            'mobile_locations.*.city_municipalities.*.barangays.*.barangay' => ['required', 'string'],

            "barangay_psgc_id" => [
                "required",
                "sometimes",
                $this->route()->target_location
                    ? "unique:target_locations,barangay_psgc_id," . $this->route()->target_location
                    : "unique:target_locations,barangay_psgc_id",
            ],

            "form_id" => [
                "required",
                "sometimes",
                "exists:forms,id"
            ],
            "form_history_id" => [
                "required",
                "sometimes",
                "exists:form_histories,id"
            ],
            "response_limit" => [
                "required",
                "sometimes",
                "integer",
                "min:1"
            ],
            "is_final" => [
                "boolean",
                "sometimes"
            ],
            "is_done" => [
                "boolean",
                "sometimes"
            ],

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $locations = $this->input('mobile_locations', []);

            $seenRegions = [];
            $seenProvinces = [];

            foreach ($locations as $locIndex => $location) {
                $hasRegion = !empty($location['region_name']) && !empty($location['region_psgc_id']);
                $hasProvince = !empty($location['province_name']) && !empty($location['province_psgc_id']);

                // 🚨 Require at least one
                if (!$hasRegion && !$hasProvince) {
                    $validator->errors()->add(
                        "mobile_locations.$locIndex",
                        "Either region (name & PSGC ID) or province (name & PSGC ID) must be provided."
                    );
                }

                // ✅ Unique regions (only for NCR)
                if ($hasRegion) {
                    $isNCR = $location['region_psgc_id'] === '1300000000' ||
                        stripos($location['region_name'], 'National Capital Region') !== false ||
                        stripos($location['region_name'], 'NCR') !== false;

                    if ($isNCR) {
                        $key = $location['region_name'] . '|' . $location['region_psgc_id'];
                        if (in_array($key, $seenRegions)) {
                            $validator->errors()->add(
                                "mobile_locations.$locIndex.region_name",
                                "Duplicate NCR region found. Only one NCR entry is allowed."
                            );
                        }
                        $seenRegions[] = $key;
                    }
                }

                // ✅ Unique provinces
                if ($hasProvince) {
                    $key = $location['province_name'] . '|' . $location['province_psgc_id'];
                    if (in_array($key, $seenProvinces)) {
                        $validator->errors()->add(
                            "mobile_locations.$locIndex.province_name",
                            "Duplicate province found: {$location['province_name']}."
                        );
                    }
                    $seenProvinces[] = $key;
                }

                // ✅ Unique municipalities inside this location
                $seenMunicipalities = [];
                foreach ($location['city_municipalities'] ?? [] as $munIndex => $municipality) {
                    if (!empty($municipality['city_municipality_psgc_id']) && !empty($municipality['city_municipality'])) {
                        $munKey = $municipality['city_municipality'] . '|' . $municipality['city_municipality_psgc_id'];
                        if (in_array($munKey, $seenMunicipalities)) {
                            $validator->errors()->add(
                                "mobile_locations.$locIndex.city_municipalities.$munIndex.city_municipality",
                                "Duplicate city/municipality found: {$municipality['city_municipality']}."
                            );
                        }
                        $seenMunicipalities[] = $munKey;
                    }

                    // 🚨 NEW: Validate that either barangays or sub_municipalities is provided (not both empty)
                    $hasBarangays = !empty($municipality['barangays']) && count($municipality['barangays']) > 0;
                    $hasSubMunicipalities = !empty($municipality['sub_municipalities']) && count($municipality['sub_municipalities']) > 0;

                    if (!$hasBarangays && !$hasSubMunicipalities) {
                        $validator->errors()->add(
                            "mobile_locations.$locIndex.city_municipalities.$munIndex",
                            "Either barangays or sub-municipalities must be provided for {$municipality['city_municipality']}."
                        );
                    }

                    // ✅ Unique barangays inside this municipality
                    $seenBarangays = [];
                    foreach ($municipality['barangays'] ?? [] as $brgyIndex => $barangay) {
                        if (!empty($barangay['barangay_psgc_id']) && !empty($barangay['barangay'])) {
                            $brgyKey = $barangay['barangay'] . '|' . $barangay['barangay_psgc_id'];
                            if (in_array($brgyKey, $seenBarangays)) {
                                $validator->errors()->add(
                                    "mobile_locations.$locIndex.city_municipalities.$munIndex.barangays.$brgyIndex.barangay",
                                    "Duplicate barangay found: {$barangay['barangay']}."
                                );
                            }
                            $seenBarangays[] = $brgyKey;
                        }
                    }

                    // ✅ Unique sub-municipalities inside this municipality
                    $seenSubMunicipalities = [];
                    foreach ($municipality['sub_municipalities'] ?? [] as $subMunIndex => $subMunicipality) {
                        if (!empty($subMunicipality['sub_municipalities_psgc_id']) && !empty($subMunicipality['sub_municipalities'])) {
                            $subMunKey = $subMunicipality['sub_municipalities'] . '|' . $subMunicipality['sub_municipalities_psgc_id'];
                            if (in_array($subMunKey, $seenSubMunicipalities)) {
                                $validator->errors()->add(
                                    "mobile_locations.$locIndex.city_municipalities.$munIndex.sub_municipalities.$subMunIndex.sub_municipalities",
                                    "Duplicate sub-municipality found: {$subMunicipality['sub_municipalities']}."
                                );
                            }
                            $seenSubMunicipalities[] = $subMunKey;
                        }
                    }
                }
            }
        });
    }


    public function messages()
    {
        return [
            'barangay_psgc_id.unique' => 'The selected barangay already exists with the same region and city/municipality.',
            // Region
            'mobile_locations.*.region_name.required_with' => 'The region name is required when region PSGC ID is provided.',
            'mobile_locations.*.region_psgc_id.required_with' => 'The region PSGC ID is required when region name is provided.',
            'mobile_locations.*.region_name.prohibited_unless' => 'The region name is not allowed when a province is provided.',
            'mobile_locations.*.region_psgc_id.prohibited_unless' => 'The region PSGC ID is not allowed when a region is provided.',

            // Province
            'mobile_locations.*.province_name.required_with' => 'The province name is required when province PSGC ID is provided.',
            'mobile_locations.*.province_psgc_id.required_with' => 'The province PSGC ID is required when province name is provided.',
            'mobile_locations.*.province_name.prohibited_unless' => 'The province name is not allowed when a region is provided.',
            'mobile_locations.*.province_psgc_id.prohibited_unless' => 'The province PSGC ID is not allowed when a region is provided.',

            // City municipalities
            'mobile_locations.*.city_municipalities.required' => 'At least one city/municipality is required.',
            'mobile_locations.*.city_municipalities.*.city_municipality_psgc_id.required' => 'The city/municipality PSGC ID is required.',
            'mobile_locations.*.city_municipalities.*.city_municipality.required' => 'The city/municipality name is required.',

            // Barangays
            'mobile_locations.*.city_municipalities.*.barangays.*.barangay_psgc_id.required' => 'The barangay PSGC ID is required.',
            'mobile_locations.*.city_municipalities.*.barangays.*.barangay.required' => 'The barangay name is required.',
        ];
    }
}
