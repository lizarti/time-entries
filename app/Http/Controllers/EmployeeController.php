<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->filled('company_id')) {
            $company = Company::findOrFail($request->integer('company_id'));

            return EmployeeResource::collection(
                $company->employees()->orderBy('name')->get()
            );
        }

        return EmployeeResource::collection(
            Employee::orderBy('name')->get()
        );
    }
}
