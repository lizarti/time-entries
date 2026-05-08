<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(Request $request, ?Company $company = null): AnonymousResourceCollection
    {
        if ($company === null && $request->filled('company_id')) {
            $company = Company::findOrFail((int) $request->input('company_id'));
        }

        $employees = $company
            ? $company->employees()->orderBy('name')->get()
            : Employee::orderBy('name')->get();

        return EmployeeResource::collection($employees);
    }
}
