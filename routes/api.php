<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BusinessUnitController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\SubUnitController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\TargetLocationController;


Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    // ymir coa
    Route::resource("companies", CompanyController::class)->middleware(['abilities:masterlist:companies:sync']);
    Route::resource("business-units", BusinessUnitController::class)->middleware(['abilities:masterlist:business-units:sync']);
    Route::resource("departments", DepartmentController::class)->middleware(['abilities:masterlist:departments:sync']);
    Route::resource("units", UnitController::class)->middleware(['abilities:masterlist:units:sync']);
    Route::resource("sub-units", SubUnitController::class)->middleware(['abilities:masterlist:sub-units:sync']);
    Route::resource("locations", LocationController::class)->middleware(['abilities:masterlist:locations:sync']);

    // Role Controller
    Route::put('role-archived/{id}', [RoleController::class, 'archived'])->middleware(['abilities:user-management:role-management:crud']);
    Route::resource("role", RoleController::class)->middleware(['abilities:user-management:role-management:crud']);

    // User Controller
    Route::put('user-archived/{id}', [UserController::class, 'archived'])->middleware(['abilities:user-management:user-accounts:crud']);
    Route::resource("user", UserController::class)->middleware(['abilities:user-management:user-accounts:crud']);

    //Form Controller
    Route::put('form-archived/{id}', [FormController::class, 'archived'])->middleware(['abilities:form-management:crud']);
    Route::resource("forms", FormController::class)->middleware(['abilities:form-management:crud']);

    //Locations Controller
    Route::put('target-location-archived/{id}', [TargetLocationController::class, 'archived'])->middleware(['abilities:target-locations:crud']);
    Route::resource("target-locations", TargetLocationController::class)->middleware(['abilities:target-locations:crud']);

    // auth controller
    Route::patch('changepassword', [AuthController::class, 'changedPassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::patch('resetpassword/{id}', [AuthController::class, 'resetPassword']);

});
