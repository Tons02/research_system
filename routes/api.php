<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BusinessUnitController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\FootCountController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\SubUnitController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\FormHistoriesController;
use App\Http\Controllers\Api\QuestionAnswerController;
use App\Http\Controllers\Api\SurveyAnswerController;
use App\Http\Controllers\Api\TargetLocationController;
use App\Http\Controllers\Api\VehicleCountController;

Route::post('login', [AuthController::class, 'login']);
// // ymir coa
// Route::resource("companies", CompanyController::class);
// Route::resource("business-units", BusinessUnitController::class);
// Route::resource("departments", DepartmentController::class);
// Route::resource("units", UnitController::class);
// Route::resource("sub-units", SubUnitController::class);
// Route::resource("locations", LocationController::class);

// // Role Controller
// Route::put('role-archived/{id}', [RoleController::class, 'archived']);
// Route::resource("role", RoleController::class);

// // User Controller
// Route::put('user-archived/{id}', [UserController::class, 'archived']);
// Route::resource("user", UserController::class);

// //Form Controller
// Route::put('form-archived/{id}', [FormController::class, 'archived']);
// Route::resource("forms", FormController::class);

// //Locations Controller
// Route::put('target-location-archived/{id}', [TargetLocationController::class, 'archived']);
// Route::resource("target-locations", TargetLocationController::class);

// // auth controller
// Route::patch('changepassword', [AuthController::class, 'changedPassword'])->middleware(['abilities:user-management:user-accounts:crud']);
// Route::post('logout', [AuthController::class, 'logout']);
// Route::patch('resetpassword/{id}', [AuthController::class, 'resetPassword'])->middleware(['abilities:user-management:user-accounts:crud']);

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
    Route::get('users-export', [UserController::class, 'export'])->middleware(['abilities:user-management:user-accounts:crud']);
    Route::get('user', [UserController::class, 'index']);
    // Protected resource routes (CRUD operations require middleware)
    Route::middleware(['abilities:user-management:user-accounts:crud'])->group(function () {
        Route::resource('user', UserController::class)->except(['index']);
    });

    //Form Controller
    Route::put('form-archived/{id}', [FormController::class, 'archived'])->middleware(['abilities:form-management:crud']);
    Route::resource("forms", FormController::class)->middleware(['abilities:form-management:crud']);

    //Form Controller
    Route::put('form-history-archived/{id}', [FormHistoriesController::class, 'archived'])->middleware(['abilities:form-management:crud']);

    Route::get('forms-history', [FormHistoriesController::class, 'index']);
    // Protected routes (create, store, edit, update, destroy)
    Route::middleware(['abilities:form-management:crud'])->group(function () {
        Route::resource('forms-history', FormHistoriesController::class)->except(['index']);
    });;

    //Locations Controller
    Route::put('target-location-archived/{id}', [TargetLocationController::class, 'archived'])->middleware(['abilities:target-locations:crud']);
    Route::patch('target-location-finalized/{id}', [TargetLocationController::class, 'finalized'])->middleware(['abilities:target-locations:crud']);
    Route::patch('target-location-end-survey/{id}', [TargetLocationController::class, 'endSurvey'])->middleware(['abilities:target-locations:crud']);
    Route::patch('target-location-skip-countdown/{id}', [TargetLocationController::class, 'skipCountdown'])->middleware(['abilities:target-locations:crud']);

    // Public routes: show and index (if you want index public too)
    Route::get('target-locations/{target_location}', [TargetLocationController::class, 'show']);
    Route::get('target-locations', [TargetLocationController::class, 'index']);

    // Protected routes (create, store, edit, update)
    Route::middleware(['abilities:target-locations:crud'])->group(function () {
        Route::resource('target-locations', TargetLocationController::class)->except(['show', 'index']);
    });


    // auth controller
    Route::patch('changepassword', [AuthController::class, 'changedPassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::patch('resetpassword/{id}', [AuthController::class, 'resetPassword'])->middleware(['abilities:user-management:user-accounts:crud']);

    //Survey Answer Controller
    Route::put('survey-answer-archived/{id}', [SurveyAnswerController::class, 'archived'])->middleware(['abilities:reports:survey-answer:crud-export']);
    Route::resource("survey-answers", SurveyAnswerController::class)->middleware(['abilities:reports:survey-answer:crud-export']);
    Route::get('survey-answer-export', [SurveyAnswerController::class, 'export'])->middleware(['abilities:reports:survey-answer:crud-export']);

    //Question Answers Controller
    Route::resource("question-answers", QuestionAnswerController::class)->middleware(['abilities:question-answer:crud']);

    //Vehicle Count Controller
    Route::put('vehicle-count-archived/{id}', [VehicleCountController::class, 'archived'])->middleware(['abilities:reports:vehicle-count:crud-export']);
    Route::get('vehicle-count-export', [VehicleCountController::class, 'export'])->middleware(['abilities:reports:vehicle-count:crud-export']);
    Route::resource("vehicle-counts", VehicleCountController::class)->middleware(['abilities:reports:vehicle-count:crud-export']);

    //Foot Count Controller
    Route::put('foot-count-archived/{id}', [FootCountController::class, 'archived'])->middleware(['abilities:reports:foot-count:crud-export']);
    Route::get('foot-count-export', [FootCountController::class, 'export'])->middleware(['abilities:reports:foot-count:crud-export']);
    Route::resource("foot-counts", FootCountController::class)->middleware(['abilities:reports:foot-count:crud-export']);
});
