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


Route::post('login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    // ymir coa 
    Route::resource("companies", CompanyController::class)->middleware(['abilities:masterlist:sync']);
    Route::resource("business-unit", BusinessUnitController::class)->middleware(['abilities:masterlist:sync']);
    Route::resource("departments", DepartmentController::class)->middleware(['abilities:masterlist:sync']);
    Route::resource("units", UnitController::class)->middleware(['abilities:masterlist:sync']);
    Route::resource("sub-units", SubUnitController::class)->middleware(['abilities:masterlist:sync']);
    Route::resource("locations", LocationController::class)->middleware(['abilities:masterlist:sync']);

    //Role Controller
    Route::put('role-archived/{id}', [RoleController::class, 'archived'])->middleware(['abilities:role-management:crud']);
    Route::resource("role", RoleController::class)->middleware(['abilities:role-management:crud']);

    //User Controller
    Route::put('user-archived/{id}', [UserController::class, 'archived'])->middleware(['abilities:user-management:crud']);
    Route::resource("user", UserController::class)->middleware(['abilities:user-management:crud']);

    // auth controller 
    Route::patch('changepassword', [AuthController::class, 'changedPassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::patch('resetpassword/{id}', [AuthController::class, 'resetPassword']);
});
