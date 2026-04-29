<?php

namespace Polirium\Modules\Task\Http\Livewire\Project;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'code' => '',
        'name' => '',
        'status' => '',
        'priority' => '',
    ];

    public $statuses = [];
    public $priorities = [];

    public function mount()
    {
        $this->statuses = [
            'planning' => __('modules/task::status.planning'),
            'active' => __('modules/task::status.active'),
            'on_hold' => __('modules/task::status.on_hold'),
            'completed' => __('modules/task::status.completed'),
            'cancelled' => __('modules/task::status.cancelled'),
        ];

        $this->priorities = [
            'low' => __('modules/task::priority.low'),
            'medium' => __('modules/task::priority.medium'),
            'high' => __('modules/task::priority.high'),
            'urgent' => __('modules/task::priority.urgent'),
        ];
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-project-filter', $value, $key);
    }

    public function clearFilter()
    {
        $this->search = [
            'code' => '',
            'name' => '',
            'status' => '',
            'priority' => '',
        ];
        $this->dispatch('datatable-project-filter-clear');
    }

    public function render()
    {
        return view('modules/task::project.filter-sidebar');
    }
}
