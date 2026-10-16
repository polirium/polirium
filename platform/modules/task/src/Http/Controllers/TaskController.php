<?php

namespace Polirium\Modules\Task\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Polirium\Core\Base\Http\Controllers\BaseController;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Task\Http\Requests\StoreTaskRequest;
use Polirium\Modules\Task\Http\Requests\UpdateTaskRequest;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;

class TaskController extends BaseController
{
    public function index(): View
    {
        return view('modules/task::task.index');
    }

    public function create(Request $request): View
    {
        $task = new Task([
            'status' => 'todo',
            'priority' => 'medium',
            'project_id' => $request->integer('project_id') ?: null,
            'parent_id' => $request->integer('parent_id') ?: null,
        ]);

        if ($task->parent_id && ($parent = Task::query()->visible()->find($task->parent_id))) {
            $task->project_id = $parent->project_id;
        }

        return view('modules/task::task.create', $this->formData($task));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create($this->applyStatusDates(new Task(), $request->validated()) + [
            'uuid' => (string) Str::uuid(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'branch_id' => user_branch(),
        ]);

        return redirect()->route('admin.tasks.show', $task->id)
            ->with('success', __('modules/task::task.created_successfully'));
    }

    public function show($id): View
    {
        $task = Task::query()->visible()->with([
            'project', 'assignedTo', 'createdBy', 'parent',
            'children.assignedTo',
            'dependents.predecessor', 'dependencies.successor',
            'comments' => fn ($q) => $q->latest(),
            'comments.user',
            'timeLogs' => fn ($q) => $q->orderByDesc('log_date')->orderByDesc('id'),
            'timeLogs.user',
            'attachments.uploadedBy',
        ])->findOrFail($id);

        $blocked = array_merge([$task->id], $task->dependents->pluck('predecessor_id')->all());
        $dependencyCandidates = Task::query()->visible()
            ->when($task->project_id, fn ($q) => $q->where('project_id', $task->project_id), fn ($q) => $q->whereNull('project_id'))
            ->whereNotIn('id', $blocked)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('modules/task::task.show', compact('task', 'dependencyCandidates'));
    }

    public function edit($id): View
    {
        return view('modules/task::task.edit', $this->formData(Task::findVisibleOrFail($id)));
    }

    public function update(UpdateTaskRequest $request, $id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $data = $this->applyStatusDates($task, $request->validated());

        // Once time is logged, actual hours are the sum of the logs.
        if ($task->timeLogs()->exists()) {
            unset($data['actual_hours']);
        }

        $task->update($data + ['updated_by' => auth()->id()]);

        return redirect()->route('admin.tasks.show', $task->id)
            ->with('success', __('modules/task::task.updated_successfully'));
    }

    /**
     * Quick status change from the task page.
     */
    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $data = $request->validate(['status' => ['required', Rule::in(Task::STATUSES)]]);

        $task->update($this->applyStatusDates($task, $data) + ['updated_by' => auth()->id()]);

        return back()->with('success', __('modules/task::task.status_updated', ['status' => $task->status_label]));
    }

    public function destroy($id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);

        if ($task->children()->exists()) {
            return back()->with('error', __('modules/task::task.cannot_delete_with_children', ['count' => $task->children()->count()]));
        }

        $projectId = $task->project_id;
        $task->delete();

        return redirect()->to($projectId && Project::query()->visible()->whereKey($projectId)->exists()
            ? route('admin.projects.show', $projectId)
            : route('admin.tasks.index'))
            ->with('success', __('modules/task::task.deleted_successfully'));
    }

    public function gantt(): View
    {
        $projects = Project::query()->visible()->orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name']);

        $all = Task::query()->visible()
            ->with(['project:id,name', 'assignedTo:id,name', 'dependencies:id,predecessor_id,successor_id'])
            ->whereNotNull('planned_start_date')
            ->whereNotNull('planned_end_date')
            ->orderBy('planned_start_date')
            ->get();

        // Parents first, each followed by its subtasks, so the chart reads as a tree.
        $byParent = $all->groupBy(fn (Task $task) => $all->contains('id', $task->parent_id) ? $task->parent_id : 0);
        $ordered = collect();
        $walk = function ($parentId, int $depth) use (&$walk, $byParent, $ordered) {
            foreach ($byParent->get($parentId, collect()) as $task) {
                $ordered->push([$task, $depth]);
                $walk($task->id, $depth + 1);
            }
        };
        $walk(0, 0);

        $tasks = $ordered->map(fn (array $row) => [
            'id' => $row[0]->id,
            'name' => $row[0]->name,
            'code' => $row[0]->code,
            'status' => $row[0]->status,
            'priority' => $row[0]->priority,
            'start' => $row[0]->planned_start_date->format('Y-m-d'),
            'end' => $row[0]->planned_end_date->format('Y-m-d'),
            'progress' => $row[0]->status === 'done' ? 100 : $row[0]->progress,
            'project_name' => $row[0]->project?->name ?? '',
            'project_id' => $row[0]->project_id,
            'assigned_to' => $row[0]->assignedTo?->name ?? '',
            'assigned_to_id' => $row[0]->assigned_to,
            'is_overdue' => $row[0]->is_overdue,
            'depth' => $row[1],
            'url' => route('admin.tasks.show', $row[0]->id),
            'dependencies' => $row[0]->dependencies->pluck('successor_id')->all(),
        ])->values();

        return view('modules/task::task.gantt', compact('projects', 'tasks', 'users'));
    }

    public function kanban(): View
    {
        return view('modules/task::task.kanban');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Task $task): array
    {
        $projects = Project::query()->visible()
            ->where(fn ($q) => $q->active()->when($task->project_id, fn ($q) => $q->orWhere('id', $task->project_id)))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        // Any visible task can be a parent, except the task itself and its subtasks.
        $excluded = $task->exists ? array_merge([$task->id], $task->descendantIds()) : [];
        $parents = Task::query()->visible()
            ->whereNotIn('id', $excluded)
            ->where(fn ($q) => $q->whereNotIn('status', ['done', 'cancelled'])
                ->when($task->parent_id, fn ($q) => $q->orWhere('id', $task->parent_id)))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'project_id']);

        $users = User::query()->orderBy('name')->get(['id', 'name', 'username']);

        return compact('task', 'projects', 'parents', 'users');
    }

    /**
     * Keep actual dates and progress consistent with the status.
     */
    private function applyStatusDates(Task $task, array $data): array
    {
        $status = $data['status'] ?? $task->status;

        if ($status === 'in_progress' && empty($data['actual_start_date']) && ! $task->actual_start_date) {
            $data['actual_start_date'] = now()->toDateString();
        }

        if ($status === 'done') {
            $data['progress_percentage'] = 100;

            if (empty($data['actual_end_date']) && ! $task->actual_end_date) {
                $data['actual_end_date'] = now()->toDateString();
            }

            if (empty($data['actual_start_date']) && ! $task->actual_start_date) {
                $data['actual_start_date'] = $data['actual_end_date'] ?? now()->toDateString();
            }
        }

        return $data;
    }
}
