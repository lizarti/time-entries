<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/companies', [CompanyController::class, 'index']);

Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/tasks', [TaskController::class, 'index']);

Route::get('/time-entries/summary', [TimeEntryController::class, 'summary']);
Route::get('/time-entries', [TimeEntryController::class, 'index']);
Route::post('/time-entries/bulk', [TimeEntryController::class, 'bulkStore']);
Route::put('/time-entries/{time_entry}', [TimeEntryController::class, 'update']);
