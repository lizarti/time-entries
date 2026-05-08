<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Rules\BelongsToCompany;
use App\Rules\EmployeeAssignedToProject;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entry     = $this->route('time_entry');
        $companyId = $entry?->company_id;
        $projectId = (int) $this->input('project_id') ?: null;

        return [
            'employee_id' => [
                'required', 'integer', 'exists:employees,id',
                new BelongsToCompany(Employee::class, $companyId),
                new EmployeeAssignedToProject($projectId),
            ],
            'project_id'  => [
                'required', 'integer', 'exists:projects,id',
                new BelongsToCompany(Project::class, $companyId),
            ],
            'task_id'     => [
                'required', 'integer', 'exists:tasks,id',
                new BelongsToCompany(Task::class, $companyId),
            ],
            'date'        => ['required', 'date'],
            'hours'       => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
