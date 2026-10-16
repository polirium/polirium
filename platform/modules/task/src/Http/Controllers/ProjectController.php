<?php

namespace Polirium\Modules\Task\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Polirium\Core\Base\Http\Controllers\BaseController;
use Polirium\Modules\Task\Http\Requests\StoreProjectRequest;
use Polirium\Modules\Task\Http\Requests\UpdateProjectRequest;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;

class ProjectController extends BaseController
{
    public function index(): View
    {
        return view('modules/task::project.index');
    }

    public function create(): View
    {
        return view('modules/task::project.create', [
            'project' => new Project(['status' => 'planning', 'priority' => 'medium', 'budget' => 0]),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated() + [
            'uuid' => (string) Str::uuid(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'branch_id' => user_branch(),
        ]);

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', __('modules/task::project.created_successfully'));
    }

    public function show($id): View
    {
        $project = Project::query()->visible()
            ->with(['branch', 'createdBy', 'tasks' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'), 'tasks.assignedTo', 'tasks.parent'])
            ->findOrFail($id);

        $tasks = $project->tasks;
        $stats = [
            'total' => $tasks->count(),
            'done' => $tasks->where('status', 'done')->count(),
            'overdue' => $tasks->filter(fn (Task $task) => $task->is_overdue)->count(),
            'by_status' => collect(Task::STATUSES)->mapWithKeys(fn ($status) => [$status => $tasks->where('status', $status)->count()]),
            'estimated_hours' => (float) $tasks->sum('estimated_hours'),
            'actual_hours' => (float) $tasks->sum('actual_hours'),
        ];

        return view('modules/task::project.show', compact('project', 'stats'));
    }

    public function edit($id): View
    {
        return view('modules/task::project.edit', ['project' => Project::findVisibleOrFail($id)]);
    }

    public function update(UpdateProjectRequest $request, $id): RedirectResponse
    {
        $project = Project::findVisibleOrFail($id);
        $project->update($request->validated() + ['updated_by' => auth()->id()]);

        return redirect()->route('admin.projects.show', $project->id)
            ->with('success', __('modules/task::project.updated_successfully'));
    }

    public function destroy($id): RedirectResponse
    {
        $project = Project::findVisibleOrFail($id);

        // Deleting a project cascades to its tasks in the database; never do that silently.
        if ($count = $project->tasks()->count()) {
            return back()->with('error', __('modules/task::project.cannot_delete_with_tasks', ['count' => $count]));
        }

        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', __('modules/task::project.deleted_successfully'));
    }
}
