<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Company;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Company $company): AnonymousResourceCollection
    {
        return TaskResource::collection($company->tasks);
    }
}
