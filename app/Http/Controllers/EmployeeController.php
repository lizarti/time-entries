<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Models\Company;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(Company $company): AnonymousResourceCollection
    {
        return EmployeeResource::collection($company->employees);
    }
}
