<?php

namespace Polirium\Modules\Task\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;

/**
 * Shared rules for creating and editing a task.
 */
abstract class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Empty numeric fields mean 0: the columns are NOT NULL with a default.
     */
    protected function prepareForValidation(): void
    {
        $defaults = ['estimated_hours' => 0, 'actual_hours' => 0, 'progress_percentage' => 0, 'sort_order' => 0];

        $this->merge(collect($defaults)
            ->filter(fn ($default, $key) => $this->input($key) === null || $this->input($key) === '')
            ->all());
    }

    protected function taskId(): ?int
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $taskId = $this->taskId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', function (string $attribute, $value, Closure $fail) {
                if ($value && ! Project::query()->visible()->whereKey($value)->exists()) {
                    $fail(__('modules/task::task.validation_project_unavailable'));
                }
            }],
            'parent_id' => ['nullable', 'integer', function (string $attribute, $value, Closure $fail) use ($taskId) {
                if (! $value) {
                    return;
                }

                $parent = Task::query()->visible()->find($value);

                if (! $parent) {
                    $fail(__('modules/task::task.validation_parent_unavailable'));

                    return;
                }

                if ($taskId && ((int) $value === $taskId || in_array((int) $value, Task::find($taskId)?->descendantIds() ?? [], true))) {
                    $fail(__('modules/task::task.validation_parent_cycle'));

                    return;
                }

                if ((string) $parent->project_id !== (string) $this->input('project_id')) {
                    $fail(__('modules/task::task.validation_parent_project'));
                }
            }],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'actual_start_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:actual_start_date'],
            'estimated_hours' => ['numeric', 'min:0', 'max:99999999'],
            'actual_hours' => ['numeric', 'min:0', 'max:99999999'],
            'progress_percentage' => ['numeric', 'min:0', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return trans('modules/task::task.attributes');
    }
}
