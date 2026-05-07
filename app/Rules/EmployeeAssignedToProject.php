<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class EmployeeAssignedToProject implements ValidationRule
{
    public function __construct(
        private readonly ?int $projectId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->projectId) {
            return;
        }

        $assigned = DB::table('employee_project')
            ->where('employee_id', $value)
            ->where('project_id', $this->projectId)
            ->exists();

        if (! $assigned) {
            $fail('The :attribute is not assigned to the specified project.');
        }
    }
}
