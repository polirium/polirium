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
    public $tasks = [];

    public $columns = [];

    public $projects = [];

    public $users = [];

    public $filter_project_id = null;

    public $filter_assigned_to = null;

    public function mount(): void
    {
        $this->projects = Project::active()
            ->when(user_branch(), fn ($q) => $q->where('branch_id', user_branch()))
            ->orderBy('name')
            ->get();

        $this->users = User::orderBy('name')->get();

        $this->columns = [
            ['id' => 'backlog', 'title' => __('modules/task::status.backlog'), 'color' => 'muted'],
            ['id' => 'todo', 'title' => __('modules/task::status.todo'), 'color' => 'secondary'],
            ['id' => 'in_progress', 'title' => __('modules/task::status.in_progress'), 'color' => 'primary'],
            ['id' => 'review', 'title' => __('modules/task::status.review'), 'color' => 'info'],
            ['id' => 'done', 'title' => __('modules/task::status.done'), 'color' => 'success'],
        ];

        $this->loadTasks();
    }

    public function loadTasks(): void
    {
        $query = Task::with(['project', 'assignedTo', 'parent'])
            ->when(user_branch(), fn ($q) => $q->where('branch_id', user_branch()))
            ->whereIn('status', array_column($this->columns, 'id'))
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($this->filter_project_id) {
            $query->where('project_id', $this->filter_project_id);
        }

        if ($this->filter_assigned_to) {
            $query->where('assigned_to', $this->filter_assigned_to);
        }

        $allTasks = $query->get();

        $this->tasks = [];
        foreach ($this->columns as $column) {
            $this->tasks[$column['id']] = $allTasks->where('status', $column['id'])->values();
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
        DB::beginTransaction();

        try {
            foreach ($orderedTaskIds as $order => $taskId) {
                Task::where('id', $taskId)->update(['sort_order' => $order]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', trans('modules/task::task.error_occurred') . $e->getMessage());
        }
    }

    public function updateTaskStatus(int $taskId, string $newStatus): void
    {
        $this->authorize('tasks.edit');
        $task = Task::find($taskId);
        if (! $task) {
            return;
        }

        DB::beginTransaction();

        try {
            $task->status = $newStatus;

            if ($newStatus === 'in_progress' && ! $task->actual_start_date) {
                $task->actual_start_date = now();
            }

            if ($newStatus === 'done' && ! $task->actual_end_date) {
                $task->actual_end_date = now();
                $task->progress_percentage = 100;
            }

            $task->save();

            DB::commit();
            $this->loadTasks();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', trans('modules/task::task.error_occurred') . $e->getMessage());
        }
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
