<?php

namespace App\Ai\Tools;

use App\Models\Employee;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchEmployee implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Use this tool to search for an employee by name within a specific company. 
        This is useful for finding the correct employee when the user provides an employee name in their message.
        
        If the employee is found, return the employee name. If not found, return "Employee not found."';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $companyId = $request->str('company_id');
        $employeeName = $request->str('employee_name');

        $employee = Employee::whereHas('companies', function ($query) use ($companyId) {
            $query->where('id', $companyId);
        })
        ->where('name', $employeeName)
        ->first();

        if (!$employee) {   
            return 'Employee not found.';
        }

        return $employee->id;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->integer()->required(),
            'employee_name' => $schema->string()->required(),
        ];
    }
}
