<?php

namespace Polirium\Modules\Task\Http\Livewire\Task\Kanban;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;

class TaskKanbanComponent extends Component
{
    private const COLUMNS = ['backlog', 'todo', 'in_progress', 'review', 'done'];

    public $tasks = [];

    public $columns = [];

    public $projects = [];

    public $users = [];

    public $filter_project_id = null;

    public $filter_assigned_to = null;

    public function mount(): void
    {
        $this->authorize('tasks.kanban');

        $this->projects = Project::query()->visible()->active()->orderBy('name')->get(['id', 'name']);
        $this->users = User::orderBy('name')->get(['id', 'name']);

        $this->columns = collect(self::COLUMNS)->map(fn ($status) => [
            'id' => $status,
            'title' => __('modules/task::status.' . $status),
            'color' => Task::STATUS_COLORS[$status],
        ])->all();

        $this->loadTasks();
    }

    public function loadTasks(): void
    {
        $allTasks = Task::query()->visible()
            ->with(['project:id,name', 'assignedTo:id,name', 'parent:id,name'])
            ->whereIn('status', self::COLUMNS)
            ->when($this->filter_project_id, fn ($q) => $q->where('project_id', $this->filter_project_id))
            ->when($this->filter_assigned_to, fn ($q) => $q->where('assigned_to', $this->filter_assigned_to))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $this->tasks = [];

        foreach (self::COLUMNS as $status) {
            $this->tasks[$status] = $allTasks->where('status', $status)->values();
        }
    }

    public function updatedFilterProjectId(): void
    {
        $this->loadTasks();
    }

    public function updatedFilterAssignedTo(): void
    {
        $this->loadTasks();
    }

    #[On('task-updated')]
    #[On('task-created')]
    #[On('task-deleted')]
    public function refreshTasks(): void
    {
        $this->loadTasks();
    }

    public function updateTaskOrder(array $orderedTaskIds): void
    {
        $this->authorize('tasks.edit');

        $ids = array_values(array_filter(array_map('intval', $orderedTaskIds)));
        $visible = Task::query()->visible()->whereIn('id', $ids)->pluck('id')->flip();

        DB::transaction(function () use ($ids, $visible) {
            foreach ($ids as $order => $taskId) {
                if ($visible->has($taskId)) {
                    Task::whereKey($taskId)->update(['sort_order' => $order]);
                }
            }
        });
    }

    public function updateTaskStatus(int $taskId, string $newStatus): void
    {
        $this->authorize('tasks.edit');

        $task = Task::query()->visible()->find($taskId);

        if (! $task || ! in_array($newStatus, self::COLUMNS, true)) {
            $this->dispatch('error', __('modules/task::task.error_occurred') . __('core/base::general.not_found'));
            $this->loadTasks();

            return;
        }

        $task->status = $newStatus;

        if ($newStatus === 'in_progress' && ! $task->actual_start_date) {
            $task->actual_start_date = now();
        }

        if ($newStatus === 'done') {
            $task->progress_percentage = 100;
            $task->actual_end_date ??= now();
            $task->actual_start_date ??= now();
        }

        $task->updated_by = auth()->id();
        $task->save();

        $this->dispatch('success', __('modules/task::task.status_updated', ['status' => $task->status_label]));
        $this->loadTasks();
    }

    public function clearFilters(): void
    {
        $this->filter_project_id = null;
        $this->filter_assigned_to = null;
        $this->loadTasks();
    }

    public function render()
    {
        return view('modules/task::task.kanban-board');
    }
}
