<?php

namespace Polirium\Modules\Task\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Polirium\Core\Base\Http\Controllers\BaseController;
use Polirium\Modules\Task\Http\Requests\StoreProjectRequest;
use Polirium\Modules\Task\Http\Requests\UpdateProjectRequest;
use Polirium\Modules\Task\Models\Project;

class ProjectController extends BaseController
{
    public function index(): View
    {
        return view('modules/task::project.index');
    }

    public function create(): View
    {
        return view('modules/task::project.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        Project::create(array_merge($request->validated(), [
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'branch_id' => user_branch(),
        ]));

        return redirect()->route('admin.projects.index')
            ->with('success', __('modules/task::project.created_successfully'));
    }

    public function show($id): View
    {
        $project = Project::with(['tasks.parent', 'branch', 'createdBy'])->findOrFail($id);

        return view('modules/task::project.show', compact('project'));
    }

    public function edit($id): View
    {
        $project = Project::findOrFail($id);

        return view('modules/task::project.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, $id): RedirectResponse
    {
        $project = Project::findOrFail($id);
        $project->update(array_merge($request->validated(), [
            'updated_by' => auth()->id(),
        ]));

        return redirect()->route('admin.projects.index')
            ->with('success', __('modules/task::project.updated_successfully'));
    }

    public function destroy($id): RedirectResponse
    {
        Project::findOrFail($id)->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', __('modules/task::project.deleted_successfully'));
    }
}
