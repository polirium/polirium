<form wire:submit.prevent="applyFilters">
    <div class="mb-3">
        <label class="form-label">Tìm kiếm</label>
        <div class="input-group">
            <input type="text" class="form-control" wire:model="name" placeholder="Tên sản phẩm...">
            @if ($name)
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilter('name')">
                    {!! tabler_icon('x', ['class' => 'icon']) !!}
                </button>
            @endif
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Mã hàng</label>
        <div class="input-group">
            <input type="text" class="form-control" wire:model="code" placeholder="Mã sản phẩm...">
            @if ($code)
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilter('code')">
                    {!! tabler_icon('x', ['class' => 'icon']) !!}
                </button>
            @endif
        </div>
    </div>

    <div class="mb-3">
        <x-form::select
                        wire:model="category_id"
                        label="Nhóm hàng"
                        :options="$lists['categories']"
                        tomselect
                        placeholder="-- Tìm nhóm hàng --">
            <x-slot name="append">
                @if ($category_id)
                    <x-ui.button type="button" color="outline-secondary" icon="x" wire:click="clearFilter('category_id')" />
                @endif
                @canany(['products.create', 'products.edit'])
                    <x-ui.button type="button"
                                 :color="$category_id ? 'warning' : 'success'"
                                 :icon="$category_id ? 'pencil' : 'plus'"
                                 wire:click="$dispatch('show-modal-create-category', { id: {{ $category_id ?: 0 }} })" />
                @endcanany
            </x-slot>
        </x-form::select>
    </div>

    <div class="mb-3">
        <x-form::select
                        wire:model="trademark_id"
                        label="Thương hiệu"
                        :options="$lists['trademarks']"
                        tomselect
                        placeholder="-- Chọn thương hiệu --">
            <x-slot name="append">
                @if ($trademark_id)
                    <x-ui.button type="button" color="outline-secondary" icon="x" wire:click="clearFilter('trademark_id')" />
                @endif
                @canany(['products.create', 'products.edit'])
                    <x-ui.button type="button"
                                 :color="$trademark_id ? 'warning' : 'success'"
                                 :icon="$trademark_id ? 'pencil' : 'plus'"
                                 wire:click="$dispatch('show-modal-create-list', { id: {{ $trademark_id ?: 0 }}, title: 'Thương hiệu', tbl: 'trademarks', model: 'Trademark' })" />
                @endcanany
            </x-slot>
        </x-form::select>
    </div>

    <div class="mb-3">
        <x-form::select
                        wire:model="shelve_id"
                        label="Vị trí"
                        :options="$lists['shelves']"
                        tomselect
                        placeholder="-- Chọn vị trí --">
            <x-slot name="append">
                @if ($shelve_id)
                    <x-ui.button type="button" color="outline-secondary" icon="x" wire:click="clearFilter('shelve_id')" />
                @endif
                @canany(['products.create', 'products.edit'])
                    <x-ui.button type="button"
                                 :color="$shelve_id ? 'warning' : 'success'"
                                 :icon="$shelve_id ? 'pencil' : 'plus'"
                                 wire:click="$dispatch('show-modal-create-list', { id: {{ $shelve_id ?: 0 }}, title: 'Vị trí', tbl: 'shelves', model: 'Shelve' })" />
                @endcanany
            </x-slot>
        </x-form::select>
    </div>

    <div class="mb-3">
        <x-form::select
                        wire:model="type"
                        label="Loại hàng"
                        :options="$lists['types']"
                        placeholder="-- Chọn loại hàng --">
            <x-slot name="append">
                @if ($type)
                    <x-ui.button type="button" color="outline-secondary" icon="x" wire:click="clearFilter('type')" />
                @endif
            </x-slot>
        </x-form::select>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary" wire:click="applyFilters">
            {!! tabler_icon('search', ['class' => 'icon']) !!}
            Tìm kiếm
        </button>
        <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
            {!! tabler_icon('refresh', ['class' => 'icon']) !!}
            Xóa bộ lọc
        </button>
    </div>
</form>
