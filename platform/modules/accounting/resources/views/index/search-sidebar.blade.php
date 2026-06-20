<div>
    <div class="mb-3">
        <label class="form-label">{{ __('Khoảng thời gian') }}</label>
        <select class="form-select" wire:model.live="search.period">
            <option value="today">{{ __('Hôm nay') }}</option>
            <option value="yesterday">{{ __('Hôm qua') }}</option>
            <option value="last_7_days">{{ __('7 ngày gần nhất') }}</option>
            <option value="this_month">{{ __('Tháng này') }}</option>
            <option value="last_month">{{ __('Tháng trước') }}</option>
            <option value="custom">{{ __('Tùy chọn khoảng ngày') }}</option>
        </select>
    </div>

    @if($search['period'] === 'custom')
        <div class="mb-3">
            <label class="form-label">{{ __('Từ ngày') }}</label>
            <input type="date" class="form-control" wire:model.live="search.date_from">
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Đến ngày') }}</label>
            <input type="date" class="form-control" wire:model.live="search.date_to">
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Mã phiếu</label>
        <input type="text" class="form-control" wire:model.live.debounce.300ms="search.code" placeholder="Nhập mã phiếu...">
    </div>

    <div class="mb-3">
        <x-form::select wire:model.live="search.type_id" label="Loại thu/chi" :options="$lists['types']" />
    </div>

    <div class="mb-3">
        <x-form::select wire:model.live="search.pay_person_id" label="Người nộp/nhận" :options="$lists['pay_persons']" />
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
