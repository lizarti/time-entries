<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Company;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->filled('company_id')) {
            $company = Company::findOrFail($request->integer('company_id'));

            return TaskResource::collection(
                $company->tasks()->orderBy('name')->get()
            );
        }

        return TaskResource::collection(
            Task::orderBy('name')->get()
        );
    }
}
