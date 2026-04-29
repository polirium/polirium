<div>
    <div class="mb-3">
        <label class="form-label">Mã hóa đơn</label>
        <input type="text" class="form-control" wire:model.live.debounce.300ms="search.code" placeholder="Nhập mã hóa đơn...">
    </div>

    <div class="mb-3">
        <label class="form-label">Tên khách hàng</label>
        <input type="text" class="form-control" wire:model.live.debounce.300ms="search.customer_name" placeholder="Nhập tên khách hàng...">
    </div>

    <div class="mb-3">
        <x-form::select wire:model.live="search.status" label="Trạng thái" :options="$lists['statuses']" />
    </div>

    <div class="mb-3">
        <label class="form-label">Từ ngày</label>
        <input type="date" class="form-control" wire:model.live="search.date_from">
    </div>

    <div class="mb-3">
        <label class="form-label">Đến ngày</label>
        <input type="date" class="form-control" wire:model.live="search.date_to">
    </div>

    <div class="mb-3">
        <label class="form-label">Giá trị từ</label>
        <input type="number" class="form-control" wire:model.live.debounce.300ms="search.value_min" placeholder="0">
    </div>

    <div class="mb-3">
        <label class="form-label">Giá trị đến</label>
        <input type="number" class="form-control" wire:model.live.debounce.300ms="search.value_max" placeholder="0">
    </div>

    <div class="d-grid gap-2">
        <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
            {!! tabler_icon('refresh', ['class' => 'icon']) !!}
            Xóa bộ lọc
        </button>
    </div>
</div>
