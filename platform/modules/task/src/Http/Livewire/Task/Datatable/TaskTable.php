<?php

namespace Polirium\Modules\Task\Http\Livewire\Task\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Task\Models\Task;

final class TaskTable extends BaseTable
{
    public string $tableName = 'task-table';

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-tasks' => '$refresh',
                'datatable-task-filter' => 'applyFilter',
                'datatable-task-filter-clear' => 'clearFilter',
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
        $query = Task::query()
            ->with(['project', 'assignedTo', 'parent'])
            ->when(user_branch(), function ($q) {
                $q->where('branch_id', user_branch());
            })
            ->orderBy('sort_order')
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

        if (! empty($this->filters['project_id'] ?? null)) {
            $query->where('project_id', $this->filters['project_id']);
        }

        if (! empty($this->filters['parent_id'] ?? null)) {
            if ($this->filters['parent_id'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $this->filters['parent_id']);
            }
        }

        return $query;
    }

    public function relationSearch(): array
    {
        return [
            'project' => ['name', 'code'],
            'assignedTo' => ['name'],
            'parent' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code')
            ->add('name')
            ->add('project_name', function (Task $task) {
                return $task->project?->name ?? '-';
            })
            ->add('status_label', function (Task $task) {
                $colors = [
                    'backlog' => 'muted',
                    'todo' => 'secondary',
                    'in_progress' => 'primary',
                    'review' => 'info',
                    'done' => 'success',
                    'cancelled' => 'danger',
                ];
                $color = $colors[$task->status] ?? 'muted';
                $label = $task->status_label;

                return "<span class='badge bg-{$color}-lt text-{$color}'>{$label}</span>";
            })
            ->add('priority_label', function (Task $task) {
                $colors = [
                    'low' => 'muted',
                    'medium' => 'info',
                    'high' => 'warning',
                    'urgent' => 'danger',
                ];
                $color = $colors[$task->priority] ?? 'muted';
                $label = $task->priority_label;

                return "<span class='badge bg-{$color}-lt text-{$color}'>{$label}</span>";
            })
            ->add('assigned_to_name', function (Task $task) {
                return $task->assignedTo?->name ?? '-';
            })
            ->add('progress_percentage', function (Task $task) {
                $percentage = $task->progress_percentage;
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
            ->add('planned_dates', function (Task $task) {
                $start = $task->planned_start_date?->format('d/m/Y');
                $end = $task->planned_end_date?->format('d/m/Y');

                return $start && $end ? "{$start} - {$end}" : '-';
            })
            ->add('hours', function (Task $task) {
                $estimated = $task->estimated_hours;
                $actual = $task->actual_hours;

                return "{$actual}/{$estimated}h";
            })
            ->add('parent_name', function (Task $task) {
                return $task->parent?->name ?? '-';
            })
            ->add('is_overdue', function (Task $task) {
                if ($task->is_overdue) {
                    return "<span class='badge bg-danger-lt text-danger'>{{ __('modules/task::task.overdue') }}</span>";
                }

                return '';
            })
            ->add('created_at_formatted', function (Task $task) {
                return $task->created_at?->format('d/m/Y H:i');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Mã công việc', 'code')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('modules/task::task.name'))
                ->field('name')
                ->searchable()
                ->sortable(),

            Column::add()
                ->title(__('modules/task::project.name'))
                ->field('project_name')
                ->sortable(),

            Column::add()
                ->title(__('modules/task::task.status_label'))
                ->field('status_label')
                ->sortable(),

            Column::add()
                ->title(__('modules/task::task.priority_label'))
                ->field('priority_label')
                ->sortable(),

            Column::add()
                ->title(trans('modules/task::task.assignee'))
                ->field('assigned_to_name')
                ->searchable(),

            Column::add()
                ->title(trans('modules/task::task.expected_date'))
                ->field('planned_dates'),

            Column::add()
                ->title(trans('modules/task::task.progress'))
                ->field('progress_percentage'),

            Column::add()
                ->title(trans('modules/task::task.time'))
                ->field('hours'),

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

    public function actions(Task $row): array
    {
        return [
            Button::make('show')
                ->slot('<i class="icon icon-sm text-primary">' . tabler_icon('eye') . '</i>')
                ->class('btn btn-sm btn-ghost-primary btn-icon')
                ->route('admin.tasks.show', ['id' => $row->id])
                ->tooltip(__('core/base::general.view')),

            Button::make('edit')
                ->slot('<i class="icon icon-sm text-primary">' . tabler_icon('edit') . '</i>')
                ->class('btn btn-sm btn-ghost-primary btn-icon')
                ->route('admin.tasks.edit', ['id' => $row->id])
                ->tooltip(__('core/base::general.edit')),

            Button::make('delete')
                ->slot('<i class="icon icon-sm text-danger">' . tabler_icon('trash') . '</i>')
                ->class('btn btn-sm btn-ghost-danger btn-icon')
                ->dispatch('deleteTask', ['id' => $row->id])
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
        $this->filters = [];
        $this->resetPage();
    }

    #[On('deleteTask')]
    public function deleteTask(int $id): void
    {
        $this->authorize('tasks.destroy');
        Task::find($id)?->delete();
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
    }
}
