<?php

namespace Polirium\Modules\Product\Http\Livewire\Index;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Category;
use Polirium\Modules\Product\Http\Model\Shelve;
use Polirium\Modules\Product\Http\Model\Trademark;

class SearchSidebarComponent extends Component
{
    #[Url(as: 'category', keep: true)]
    public $category_id = '';

    #[Url(as: 'trademark', keep: true)]
    public $trademark_id = '';

    #[Url(as: 'shelve', keep: true)]
    public $shelve_id = '';

    #[Url(as: 'type', keep: true)]
    public $type = '';

    #[Url(as: 'name', keep: true)]
    public $name = '';

    #[Url(as: 'code', keep: true)]
    public $code = '';

    public array $lists = [
        'categories' => [],
        'trademarks' => [],
        'shelves' => [],
        'types' => [],
    ];

    public function mount()
    {
        $this->refreshLists();

        if (empty($this->code) && request()->filled('search')) {
            $this->code = request('search');
        }

        // Apply initial filters from URL on mount
        $this->applyFilters();
    }

    /**
     * Apply all filters at once
     */
    public function applyFilters()
    {
        $filters = [
            'category_id' => $this->category_id,
            'trademark_id' => $this->trademark_id,
            'shelve_id' => $this->shelve_id,
            'type' => $this->type,
            'name' => $this->name,
            'code' => $this->code,
        ];

        foreach ($filters as $key => $value) {
            $this->dispatch('product-search-sidebar', value: $value, key: $key);
        }
    }

    /**
     * Clear a specific filter
     */
    public function clearFilter(string $key)
    {
        $this->{$key} = '';
        if ($key === 'category_id') {
            $this->category_search = '';
        }
        $this->dispatch('product-search-sidebar', value: '', key: $key);
    }

    /**
     * Select category by name (from datalist)
     */
    public function selectCategoryByName(string $name)
    {
        // Find category ID by name
        $categoryId = array_search($name, $this->lists['categories']);
        if ($categoryId !== false) {
            $this->category_id = $categoryId;
            $this->category_search = $name;
        }
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->category_id = '';
        $this->trademark_id = '';
        $this->shelve_id = '';
        $this->type = '';
        $this->name = '';
        $this->code = '';

        $this->applyFilters();
    }

    public function render()
    {
        return view('modules/product::index.search-sidebar');
    }

    #[On('product-search-sidebar-refresh-lists')]
    public function refreshLists()
    {
        $this->lists['categories'] = Category::getHierarchicalList();
        $this->lists['trademarks'] = Trademark::select('name', 'id')->pluck('name', 'id')->all();
        $this->lists['shelves'] = Shelve::select('name', 'id')->pluck('name', 'id')->all();
        $this->lists['types'] = [
            'product' => 'Hàng hóa',
            'service' => 'Dịch vụ',
            'combo' => 'Combo',
        ];
    }
}
