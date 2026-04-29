<div class="card-header" x-data="{
    selectedValues: @entangle('checkboxValues'),
    get count() {
        return this.selectedValues.length;
    }
}" x-init="@if ($this->showDetailId) setTimeout(() => {
            window.dispatchEvent(new CustomEvent('pg-toggle-detail-table-customers-{{ $this->showDetailId }}', { detail: { collapsed: true } }));
        }, 800); @endif">
    <div class="d-flex align-items-center gap-2">
        {!! tabler_icon('users', ['class' => 'icon text-primary']) !!}
        <div>
            <h3 class="card-title mb-0">{{ trans('modules/customer::customer.name') }}</h3>
            <p class="text-muted small mb-0">{{ trans('modules/customer::customer.list_description') }}</p>
        </div>
    </div>
    <div class="card-actions">
        <div class="btn-list">
            @can('customers.destroy')
                <span class="badge bg-primary-lt text-primary ms-2" x-show="count > 0" style="display: none;">
                    Đã chọn <span x-text="count">0</span> khách hàng
                </span>
                <x-ui::button
                              color="danger"
                              icon="trash"
                              wire:click="bulkDelete"
                              wire:confirm="Bạn có chắc chắn muốn xóa các khách hàng đã chọn? Thao tác này không thể hoàn tác."
                              class="btn-bulk-delete"
                              x-show="count > 0"
                              style="display: none;">
                    {{ trans('modules/customer::customer.delete_selected') }}
                </x-ui::button>
            @endcan

            @can('customers.create')
                <x-ui::button color="white" icon="file-upload" @click="window.Livewire.dispatch('show-modal-import-customer')">
                    {{ trans('modules/customer::customer.import_excel') }}
                </x-ui::button>
            @endcan

            @if ($this->showDetailId)
                <x-ui::button color="white" icon="filter-off" onclick="window.location.href='{{ route('customers.index') }}'">
                    {{ trans('modules/customer::customer.cancel_search') }}
                </x-ui::button>
            @endif

            @can('customers.create')
                <x-ui::button color="primary" icon="user-plus" @click="$dispatch('show-modal-create-customer')">
                    {{ trans('modules/customer::customer.create') }}
                </x-ui::button>
            @endcan
        </div>
    </div>

    {{-- Confirm Delete Customer Modal --}}
    @teleport('body')
        <div x-data="{ deleteId: null }" @set-delete-customer-id.window="deleteId = $event.detail.id">
            <x-ui::confirm-modal
                                 id="modal-confirm-delete-customer"
                                 title="{{ __('modules/customer::customer.delete') }}"
                                 message="{{ __('modules/customer::customer.delete_confirm') }}"
                                 confirm-button="{{ trans('modules/customer::customer.delete') }}"
                                 confirm-color="danger"
                                 icon="alert-circle"
                                 x-on:click="window.Livewire.dispatch('triggerRemoveCustomer', { id: deleteId })" />
        </div>
    @endteleport
</div>
