<?php

namespace App\Rules;

use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BelongsToCompany implements ValidationRule
{
    public function __construct(
        private readonly string $model,
        private readonly ?int $companyId,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->companyId) {
            return;
        }

        $exists = match ($this->model) {
            Employee::class => Employee::where('id', $value)
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $this->companyId))
                ->exists(),
            default => $this->model::where('id', $value)
                ->where('company_id', $this->companyId)
                ->exists(),
        };

        if (! $exists) {
            $fail('The :attribute does not belong to the specified company.');
        }
    }
}
