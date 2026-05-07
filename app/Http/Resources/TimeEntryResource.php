<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'company'  => new CompanyResource($this->whenLoaded('company')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'project'  => new ProjectResource($this->whenLoaded('project')),
            'task'     => new TaskResource($this->whenLoaded('task')),
            'date'     => $this->date->toDateString(),
            'hours'    => (float) $this->hours,
        ];
    }
}
