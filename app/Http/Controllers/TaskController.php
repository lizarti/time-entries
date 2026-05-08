<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Company;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request, ?Company $company = null): AnonymousResourceCollection
    {
        if ($company === null && $request->filled('company_id')) {
            $company = Company::findOrFail((int) $request->input('company_id'));
        }

        $tasks = $company
            ? $company->tasks()->orderBy('name')->get()
            : Task::orderBy('name')->get();

        return TaskResource::collection($tasks);
    }
}
