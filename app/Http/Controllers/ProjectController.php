<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Company;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Company $company): AnonymousResourceCollection
    {
        return ProjectResource::collection($company->projects);
    }
}
