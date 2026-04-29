<div class="d-flex justify-content-between align-items-center mb-3 p-2">
    {{-- Tabs --}}
    <ul class="nav nav-tabs card-header-tabs m-0" role="tablist">
        <li class="nav-item" role="presentation">
            <a
               href="javascript:void(0)"
               class="nav-link {{ $type === 'receipt' ? 'active' : '' }}"
               wire:click="$set('type', 'receipt')">
                {{ tabler_icon('arrow-down-circle', ['class' => 'icon me-1']) }}
                {{ trans('modules/accounting::accounting.receipt.name') }}
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a
               href="javascript:void(0)"
               class="nav-link {{ $type === 'payment' ? 'active' : '' }}"
               wire:click="$set('type', 'payment')">
                {{ tabler_icon('arrow-up-circle', ['class' => 'icon me-1']) }}
                {{ trans('modules/accounting::accounting.payment.name') }}
            </a>
        </li>
    </ul>

    <div class="card-actions">
        @can('accountings.create')
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <x-tabler-icons::plus class="icon me-1" />
                    {{ trans('modules/accounting::accounting.create') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" @click="$dispatch('show-modal-create-accounting', { model: 'Polirium\\Modules\\Accounting\\Http\\Model\\Receipt', type: 'receipt' })">
                            <x-tabler-icons::arrow-down-circle class="icon me-2" />
                            {{ trans('modules/accounting::accounting.receipt.create') }}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" @click="$dispatch('show-modal-create-accounting', { model: 'Polirium\\Modules\\Accounting\\Http\\Model\\Payment', type: 'payment' })">
                            <x-tabler-icons::arrow-up-circle class="icon me-2" />
                            {{ trans('modules/accounting::accounting.payment.create') }}
                        </a>
                    </li>
                </ul>
            </div>
        @endcan
    </div>
</div>
