<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Index;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;

class SearchSidebarComponent extends Component
{
    public array $search = [
        'group_id' => 0,
    ];

    public array $lists = [
        'group' => [],
    ];

    public function mount()
    {
        $this->refreshLists();
    }

    public function updatedSearch(mixed $value, string $key)
    {
        $this->dispatch('vendor-search-sidebar', value: $value, key: $key);
    }

    public function render()
    {
        return view('modules/vendor::index.search-sidebar');
    }

    #[On('vendor-search-sidebar-refresh-lists')]
    public function refreshLists()
    {
        $this->lists['group'] = VendorGroup::select('name', 'id')->pluck('name', 'id')->all();
    }
}
