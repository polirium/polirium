<?php

namespace Polirium\Modules\Task\Providers;

use Polirium\Core\Base\Helpers\BaseHelper;
use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;

class TaskServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this->registerGlobalSearch();

        $this
            ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();
    }

    public function register()
    {
        $this
            ->setNamespace('modules/task')
            ->loadConfigurations(['task']);

        BaseHelper::autoload(__DIR__ . '/../../helpers');
    }

    /**
     * Projects and tasks in the header search (Ctrl+K), only those the user
     * may open.
     */
    protected function registerGlobalSearch(): void
    {
        $search = app(\Polirium\Core\Base\Search\GlobalSearch::class);

        $search->register('projects', [
            'label' => 'core/base::general.search_group_projects',
            'icon' => 'briefcase',
            'permission' => 'projects.show',
            'order' => 50,
            'search' => function (string $term, int $limit) {
                $like = \Polirium\Core\Base\Search\GlobalSearch::like($term);

                return \Polirium\Modules\Task\Models\Project::query()->visible()
                    ->where(fn ($query) => $query->where('name', 'like', '%' . $like . '%')->orWhere('code', 'like', $like . '%'))
                    ->latest('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($project) => [
                        'title' => $project->name,
                        'subtitle' => $project->code,
                        'url' => route('admin.projects.show', $project->id),
                    ]);
            },
        ]);

        $search->register('tasks', [
            'label' => 'core/base::general.search_group_tasks',
            'icon' => 'checkbox',
            'permission' => 'tasks.show',
            'order' => 60,
            'search' => function (string $term, int $limit) {
                $like = \Polirium\Core\Base\Search\GlobalSearch::like($term);

                return \Polirium\Modules\Task\Models\Task::query()->visible()
                    ->with('project:id,name')
                    ->where(fn ($query) => $query->where('name', 'like', '%' . $like . '%')->orWhere('code', 'like', $like . '%'))
                    ->latest('id')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($task) => [
                        'title' => $task->name,
                        'subtitle' => collect([$task->code, $task->project?->name])->filter()->implode(' · '),
                        'url' => route('admin.tasks.show', $task->id),
                    ]);
            },
        ]);
    }
}
