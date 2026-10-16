<?php

namespace Polirium\Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Polirium\Modules\Task\Models\Project;

/**
 * Shared rules for creating and editing a project.
 */
abstract class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An empty budget means 0: the column is NOT NULL with a default.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('budget') === null || $this->input('budget') === '') {
            $this->merge(['budget' => 0]);
        }
    }

    protected function projectId(): ?int
    {
        return null;
    }

    /**
     * @return list<string>
     */
    protected function allowedStatuses(): array
    {
        return Project::STATUSES;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('projects', 'code')->ignore($this->projectId())],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::in($this->allowedStatuses())],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'budget' => ['numeric', 'min:0', 'max:9999999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return trans('modules/task::task.attributes');
    }
}
