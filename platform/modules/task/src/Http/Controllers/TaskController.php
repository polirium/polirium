<?php

namespace Polirium\Modules\Task\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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

    public function create(): View
    {
        $users = User::orderBy('name')->get();

        return view('modules/task::task.create', compact('users'));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Task::create(array_merge($request->validated(), [
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'branch_id' => user_branch(),
        ]));

        return redirect()->route('admin.tasks.index')
            ->with('success', __('modules/task::task.created_successfully'));
    }

    public function show($id): View
    {
        $task = Task::with(['project', 'assignedTo', 'parent', 'children', 'dependencies.successor', 'dependents.predecessor'])->findOrFail($id);

        return view('modules/task::task.show', compact('task'));
    }

    public function edit($id): View
    {
        $task = Task::findOrFail($id);
        $users = User::orderBy('name')->get();

        return view('modules/task::task.edit', compact('task', 'users'));
    }

    public function update(UpdateTaskRequest $request, $id): RedirectResponse
    {
        $task = Task::findOrFail($id);
        $task->update(array_merge($request->validated(), [
            'updated_by' => auth()->id(),
        ]));

        return redirect()->route('admin.tasks.index')
            ->with('success', __('modules/task::task.updated_successfully'));
    }

    public function destroy($id): RedirectResponse
    {
        Task::findOrFail($id)->delete();

        return redirect()->route('admin.tasks.index')
            ->with('success', __('modules/task::task.deleted_successfully'));
    }

    public function gantt(): View
    {
        $isAdmin = auth()->user()?->isSuperAdmin() || auth()->user()?->hasRole('admin');

        $projects = Project::query()
            ->when(! $isAdmin && user_branch(), fn ($q) => $q->where('branch_id', user_branch()))
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        $tasks = Task::with(['project', 'assignedTo', 'dependencies.successor'])
            ->when(! $isAdmin && user_branch(), fn ($q) => $q->where('branch_id', user_branch()))
            ->whereNotNull('planned_start_date')
            ->whereNotNull('planned_end_date')
            ->orderBy('planned_start_date')
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'code' => $task->code,
                'status' => $task->status,
                'priority' => $task->priority,
                'start' => $task->planned_start_date->format('Y-m-d'),
                'end' => $task->planned_end_date->format('Y-m-d'),
                'progress' => $task->progress_percentage ?? 0,
                'project_name' => $task->project?->name ?? '',
                'project_id' => $task->project_id,
                'assigned_to' => $task->assignedTo?->name ?? '',
                'assigned_to_id' => $task->assigned_to,
                'is_overdue' => $task->is_overdue,
                'dependencies' => $task->dependencies->pluck('successor_id')->toArray(),
            ]);

        return view('modules/task::task.gantt', compact('projects', 'tasks', 'users'));
    }

    public function kanban(): View
    {
        return view('modules/task::task.kanban');
    }
}
