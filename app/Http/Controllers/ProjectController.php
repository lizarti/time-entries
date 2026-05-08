<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request, ?Company $company = null): AnonymousResourceCollection
    {
        if ($company === null && $request->filled('company_id')) {
            $company = Company::findOrFail((int) $request->input('company_id'));
        }

        $projects = $company
            ? $company->projects()->orderBy('name')->get()
            : Project::orderBy('name')->get();

        return ProjectResource::collection($projects);
    }
}
