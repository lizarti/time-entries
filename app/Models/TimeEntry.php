<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'project_id',
        'task_id',
        'date',
        'hours',
    ];

    protected $casts = [
        'date'  => 'date',
        'hours' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    // ─── Query scopes ─────────────────────────────────────────────────────────

    public function scopeWithFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                ! empty($filters['company_id']),
                fn ($q) => $q->where('time_entries.company_id', $filters['company_id']),
            )
            ->when(
                ! empty($filters['employee_id']),
                fn ($q) => $q->where('time_entries.employee_id', $filters['employee_id']),
            )
            ->when(
                ! empty($filters['project_id']),
                fn ($q) => $q->where('time_entries.project_id', $filters['project_id']),
            )
            ->when(
                ! empty($filters['task_id']),
                fn ($q) => $q->where('time_entries.task_id', $filters['task_id']),
            )
            ->when(
                ! empty($filters['date_from']),
                fn ($q) => $q->whereDate('time_entries.date', '>=', Carbon::parse($filters['date_from'])->startOfDay()),
            )
            ->when(
                ! empty($filters['date_to']),
                fn ($q) => $q->whereDate('time_entries.date', '<=', Carbon::parse($filters['date_to'])->endOfDay()),
            )
            ->when(
                ! empty($filters['search']),
                function ($q) use ($filters) {
                    $search = $filters['search'];
                    $q->where(function ($inner) use ($search) {
                        $inner
                            ->whereHas('employee', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('project',  fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('task',     fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('company',  fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    });
                },
            );
    }
}
