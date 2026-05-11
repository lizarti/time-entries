<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Rules\BelongsToCompany;
use App\Rules\EmployeeAssignedToProject;
use Illuminate\Foundation\Http\FormRequest;

class BulkInsertTimeEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'entries'   => ['required', 'array', 'min:1'],
            'entries.*' => ['array'],
        ];

        $entries = is_array($this->input('entries')) ? $this->input('entries') : [];

        foreach ($entries as $index => $entry) {
            $companyId = isset($entry['company_id']) ? (int) $entry['company_id'] : null;
            $projectId = isset($entry['project_id']) ? (int) $entry['project_id'] : null;

            $rules["entries.{$index}.company_id"]  = ['required', 'integer', 'exists:companies,id'];
            $rules["entries.{$index}.employee_id"] = [
                'required', 'integer', 'exists:employees,id',
                new BelongsToCompany(Employee::class, $companyId)
            ];
            $rules["entries.{$index}.project_id"]  = [
                'required', 'integer', 'exists:projects,id',
                new BelongsToCompany(Project::class, $companyId),
            ];
            $rules["entries.{$index}.task_id"]     = [
                'required', 'integer', 'exists:tasks,id',
                new BelongsToCompany(Task::class, $companyId),
            ];
            $rules["entries.{$index}.date"]        = ['required', 'date'];
            $rules["entries.{$index}.hours"]       = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }
}
