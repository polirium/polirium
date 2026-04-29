<?php

namespace Polirium\Modules\Task\Http\Livewire\Task;

use Livewire\Component;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;

class FilterSidebarComponent extends Component
{
    public $search = [
        'code' => '',
        'name' => '',
        'status' => '',
        'priority' => '',
        'project_id' => '',
        'parent_id' => '',
    ];

    public $statuses = [];
    public $priorities = [];
    public $projects = [];
    public $parentTasks = [];

    public function mount()
    {
        $this->statuses = [
            'backlog' => __('modules/task::status.backlog'),
            'todo' => __('modules/task::status.todo'),
            'in_progress' => __('modules/task::status.in_progress'),
            'review' => __('modules/task::status.review'),
            'done' => __('modules/task::status.done'),
            'cancelled' => __('modules/task::status.cancelled'),
        ];

        $this->priorities = [
            'low' => __('modules/task::priority.low'),
            'medium' => __('modules/task::priority.medium'),
            'high' => __('modules/task::priority.high'),
            'urgent' => __('modules/task::priority.urgent'),
        ];

        $this->projects = Project::active()
            ->when(user_branch(), fn ($q) => $q->where('branch_id', user_branch()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Add "Root tasks only" option
        $this->parentTasks = ['root' => __('modules/task::task.root_tasks_only')] + $this->parentTasks;
    }

    public function updatedSearchProjectId($value)
    {
        // Reload parent tasks when project changes
        if (! empty($value)) {
            $this->parentTasks = ['root' => __('modules/task::task.root_tasks_only')];
            $this->parentTasks += Task::where('project_id', $value)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        } else {
            $this->parentTasks = ['root' => __('modules/task::task.root_tasks_only')];
        }
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-task-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = [
            'code' => '',
            'name' => '',
            'status' => '',
            'priority' => '',
            'project_id' => '',
            'parent_id' => '',
        ];
        $this->parentTasks = ['root' => __('modules/task::task.root_tasks_only')];
        $this->dispatch('datatable-task-filter-clear');
    }

    public function render()
    {
        return view('modules/task::task.filter-sidebar');
    }
}
