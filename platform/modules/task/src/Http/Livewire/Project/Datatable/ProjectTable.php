<?php

namespace Polirium\Modules\Task\Http\Livewire\Project\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Task\Models\Project;

final class ProjectTable extends BaseTable
{
    public string $tableName = 'project-table';

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-projects' => '$refresh',
                'datatable-project-filter' => 'applyFilter',
                'datatable-project-filter-clear' => 'clearFilter',
            ]
        );
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('file-name')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $query = Project::query()
            ->with(['branch', 'createdBy'])
            ->when(user_branch(), function ($q) {
                $q->where('branch_id', user_branch());
            })
            ->orderByDesc('id');

        // Apply filters from sidebar
        if (! empty($this->filters['code'] ?? null)) {
            $query->where('code', 'like', '%' . $this->filters['code'] . '%');
        }

        if (! empty($this->filters['name'] ?? null)) {
            $query->where('name', 'like', '%' . $this->filters['name'] . '%');
        }

        if (! empty($this->filters['status'] ?? null)) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['priority'] ?? null)) {
            $query->where('priority', $this->filters['priority']);
        }

        return $query;
    }

    public function relationSearch(): array
    {
        return [
            'branch' => ['name'],
            'createdBy' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code')
            ->add('name')
            ->add('status_label', function (Project $project) {
                $colors = [
                    'planning' => 'info',
                    'active' => 'primary',
                    'on_hold' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $color = $colors[$project->status] ?? 'muted';
                $label = $project->status_label;

                return "<span class='badge bg-{$color}-lt text-{$color}'>{$label}</span>";
            })
            ->add('priority_label', function (Project $project) {
                $colors = [
                    'low' => 'muted',
                    'medium' => 'info',
                    'high' => 'warning',
                    'urgent' => 'danger',
                ];
                $color = $colors[$project->priority] ?? 'muted';
                $label = $project->priority_label;

                return "<span class='badge bg-{$color}-lt text-{$color}'>{$label}</span>";
            })
            ->add('budget_formatted', function (Project $project) {
                return core_number_format($project->budget);
            })
            ->add('progress_percentage', function (Project $project) {
                $percentage = $project->progress_percentage;
                $color = match(true) {
                    $percentage >= 100 => 'success',
                    $percentage >= 75 => 'primary',
                    $percentage >= 50 => 'info',
                    $percentage >= 25 => 'warning',
                    default => 'danger',
                };

                return "
                    <div class='progress progress-sm'>
                        <div class='progress-bar bg-{$color}' style='width: {$percentage}%' role='progressbar' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'></div>
                    </div>
                    <small class='text-muted'>{$percentage}%</small>
                ";
            })
            ->add('planned_dates', function (Project $project) {
                $start = $project->planned_start_date?->format('d/m/Y');
                $end = $project->planned_end_date?->format('d/m/Y');

                return $start && $end ? "{$start} - {$end}" : '-';
            })
            ->add('tasks_count', function (Project $project) {
                return $project->tasks_count ?? $project->tasks()->count();
            })
            ->add('created_at_formatted', function (Project $project) {
                return $project->created_at?->format('d/m/Y H:i');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Mã dự án', 'code')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('modules/task::project.name'))
                ->field('name')
                ->searchable()
                ->sortable(),

            Column::add()
                ->title(__('modules/task::project.status_label'))
                ->field('status_label')
                ->sortable(),

            Column::add()
                ->title(__('modules/task::project.priority_label'))
                ->field('priority_label')
                ->sortable(),

            Column::add()
                ->title(trans('modules/task::task.expected_date'))
                ->field('planned_dates'),

            Column::add()
                ->title(trans('modules/task::task.progress'))
                ->field('progress_percentage'),

            Column::add()
                ->title(trans('modules/task::task.budget'))
                ->field('budget_formatted')
                ->sortable(),

            Column::add()
                ->title(trans('modules/task::task.task'))
                ->field('tasks_count'),

            Column::add()
                ->title(trans('modules/task::task.created_at'))
                ->field('created_at_formatted')
                ->hidden()
                ->sortable(),

            Column::action(__('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(Project $row): array
    {
        return [
            Button::make('show')
                ->slot('<i class="icon icon-sm text-primary">' . tabler_icon('eye') . '</i>')
                ->class('btn btn-sm btn-ghost-primary btn-icon')
                ->route('admin.projects.show', ['id' => $row->id])
                ->tooltip(__('core/base::general.view')),

            Button::make('edit')
                ->slot('<i class="icon icon-sm text-primary">' . tabler_icon('edit') . '</i>')
                ->class('btn btn-sm btn-ghost-primary btn-icon')
                ->route('admin.projects.edit', ['id' => $row->id])
                ->tooltip(__('core/base::general.edit')),

            Button::make('delete')
                ->slot('<i class="icon icon-sm text-danger">' . tabler_icon('trash') . '</i>')
                ->class('btn btn-sm btn-ghost-danger btn-icon')
                ->dispatch('deleteProject', ['id' => $row->id])
                ->confirm(__('core/base::general.confirm_delete'))
                ->tooltip(__('core/base::general.delete')),
        ];
    }

    public function applyFilter($value, $key)
    {
        $this->filters[$key] = $value;
        $this->resetPage();
    }

    public function clearFilter(string $field = ''): void
    {
        if ($field === '') {
            $this->filters = [];
        }

        parent::clearFilter($field);
        $this->resetPage();
    }

    #[On('deleteProject')]
    public function deleteProject(int $id): void
    {
        $this->authorize('projects.destroy');
        Project::find($id)?->delete();
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
    }
}
