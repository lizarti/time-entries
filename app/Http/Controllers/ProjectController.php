<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->filled('company_id')) {
            $company = Company::findOrFail($request->integer('company_id'));

            return ProjectResource::collection(
                $company->projects()->orderBy('name')->get()
            );
        }

        return ProjectResource::collection(
            Project::orderBy('name')->get()
        );
    }
}
