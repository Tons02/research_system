<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FootCountController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\FormHistoriesController;
use App\Http\Controllers\Api\OneChargingController;
use App\Http\Controllers\Api\QuestionAnswerController;
use App\Http\Controllers\Api\SurveyAnswerController;
use App\Http\Controllers\Api\TargetLocationController;
use App\Http\Controllers\Api\VehicleCountController;

Route::post('login', [AuthController::class, 'login']);

// // Role Controller
// Route::put('role-archived/{id}', [RoleController::class, 'archived']);
// Route::resource("role", RoleController::class);

// // User Controller
// Route::put('user-archived/{id}', [UserController::class, 'archived']);
// Route::resource("user", UserController::class);

// Route::resource("one-charging", OneChargingController::class);

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

Route::middleware('onerdf')->post('sync_charging', [OneChargingController::class, 'sync_from_one_rdf']);


Route::middleware(['auth:sanctum'])->group(function () {


    Route::post('one-charging', [OneChargingController::class, 'store']);
    Route::get("one-charging", [OneChargingController::class, 'index']);
    // Role Controller
    Route::put('role-archived/{id}', [RoleController::class, 'archived'])->middleware(['abilities:masterlist:role-management:crud']);
    Route::resource("role", RoleController::class)->middleware(['abilities:masterlist:role-management:crud']);

    // User Controller
    Route::put('user-archived/{id}', [UserController::class, 'archived'])->middleware(['abilities:masterlist:user-accounts:crud']);
    Route::get('users-export', [UserController::class, 'export'])->middleware(['abilities:masterlist:user-accounts:crud']);
    Route::get('user', [UserController::class, 'index']);
    // Protected resource routes (CRUD operations require middleware)
    Route::middleware(['abilities:masterlist:user-accounts:crud'])->group(function () {
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
    Route::get('target-locations-users', [TargetLocationController::class, 'target_location_users']);
    Route::get('target-locations', [TargetLocationController::class, 'index']);

    // Protected routes (create, store, edit, update)
    Route::middleware(['abilities:target-locations:crud'])->group(function () {
        Route::resource('target-locations', TargetLocationController::class)->except(['show', 'index']);
    });


    // auth controller
    Route::patch('changepassword', [AuthController::class, 'changedPassword']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::patch('resetpassword/{id}', [AuthController::class, 'resetPassword'])->middleware(['abilities:masterlist:user-accounts:crud']);

    //Survey Answer Controller
    Route::post('survey-answers-multiple-sync', [SurveyAnswerController::class, 'multiple_sync']);
    Route::put('survey-answer-archived/{id}', [SurveyAnswerController::class, 'archived']);
    Route::resource("survey-answers", SurveyAnswerController::class);
    Route::get('survey-mobile-answer', [SurveyAnswerController::class, 'mobile_survey']);
    Route::get('survey-answer-export', [SurveyAnswerController::class, 'export']);

    //Question Answers Controller
    Route::resource("question-answers", QuestionAnswerController::class);

    // make the api public
    //Vehicle Count Controller
    Route::post('vehicle-counts-multiple-sync', [VehicleCountController::class, 'multiple_sync']);
    Route::put('vehicle-count-archived/{id}', [VehicleCountController::class, 'archived']);
    Route::get('vehicle-count-export', [VehicleCountController::class, 'export']);
    Route::resource("vehicle-counts", VehicleCountController::class);

    //Foot Count Controller
    Route::post('foot-counts-multiple-sync', [FootCountController::class, 'multiple_sync']);
    Route::put('foot-count-archived/{id}', [FootCountController::class, 'archived']);
    Route::get('foot-count-export', [FootCountController::class, 'export']);
    Route::resource("foot-counts", FootCountController::class);
});
