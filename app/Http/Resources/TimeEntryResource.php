<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'company_id'  => $this->company_id,
            'employee_id' => $this->employee_id,
            'project_id'  => $this->project_id,
            'task_id'     => $this->task_id,
            'date'        => $this->date->toDateString(),
            'hours'       => (float) $this->hours,
        ];
    }
}
