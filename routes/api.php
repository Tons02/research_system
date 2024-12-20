<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BusinessUnitController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\SubUnitController;
use App\Http\Controllers\Api\LocationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource("companies", CompanyController::class);
Route::resource("business-unit", BusinessUnitController::class);
Route::resource("departments", DepartmentController::class);
Route::resource("units", UnitController::class);
Route::resource("sub-units", SubUnitController::class);
Route::resource("locations", LocationController::class);

