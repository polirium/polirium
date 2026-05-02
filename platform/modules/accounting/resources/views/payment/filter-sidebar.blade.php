<div>
    {{-- Filter Panel --}}
    <x-ui::card>
        {{-- Header với icon --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                {!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}
                <span class="fw-semibold">{{ __('core/base::general.filter') }}</span>
            </div>
            <button type="button" class="btn btn-icon btn-sm btn-ghost-secondary" @click="$dispatch('toggle-sidebar')">
                {!! tabler_icon('chevrons-left', ['class' => 'icon']) !!}
            </button>
        </div>

        <div>

            {{-- 1. Search by code --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ __('modules/accounting::accounting.search_by_code') }}</label>
                <div class="input-icon">
                    <span class="input-icon-addon">
                        {!! tabler_icon('search', ['class' => 'icon']) !!}
                    </span>
                    <input
                           type="text"
                           class="form-control"
                           wire:model.live.debounce.300ms="search.code"
                           placeholder="{{ trans('modules/accounting::accounting.invoice_code_placeholder') }}">
                </div>
            </div>

            {{-- 1b. Search by Product Code/Name --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ __('Tìm theo Mã / Tên sản phẩm') }}</label>
                <div class="input-icon">
                    <span class="input-icon-addon">
                        {!! tabler_icon('package', ['class' => 'icon']) !!}
                    </span>
                    <input
                           type="text"
                           class="form-control"
                           wire:model.live.debounce.300ms="search.product_search"
                           placeholder="{{ __('Mã SP hoặc tên SP...') }}">
                </div>
            </div>

            {{-- 2. Filter by Date --}}
            <div class="mb-3"
                 x-data="{
                     fp: null,
                     activePreset: '',
                     init() {
                         this.fp = flatpickr(this.$refs.datePicker, {
                             mode: 'range',
                             dateFormat: 'Y-m-d',
                             onChange: () => { this.activePreset = ''; }
                         });
                         const v = (this.$refs.datePicker.value || '').trim();
                         const d = new Date();
                         const todayStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                         if (v === todayStr || v === todayStr + ' to ' + todayStr) {
                             this.activePreset = 'today';
                         }
                     },
                     setPreset(key) {
                         if (this.activePreset === key) {
                             this.activePreset = '';
                             this.fp.clear();
                             @this.set('search.date', '');
                             return;
                         }
                         this.activePreset = key;
                         const now = new Date();
                         let from, to;
                         switch (key) {
                             case 'today':
                                 from = to = new Date(now);
                                 break;
                             case 'yesterday': {
                                 const d = new Date(now);
                                 d.setDate(d.getDate() - 1);
                                 from = to = d;
                                 break;
                             }
                             case 'this_week': {
                                 const d = new Date(now);
                                 const day = d.getDay() || 7;
                                 from = new Date(d.setDate(d.getDate() - day + 1));
                                 to = new Date();
                                 break;
                             }
                             case 'last_week': {
                                 const d = new Date(now);
                                 const day = d.getDay() || 7;
                                 const mon = new Date(d.setDate(d.getDate() - day + 1));
                                 to = new Date(mon);
                                 to.setDate(to.getDate() - 1);
                                 from = new Date(to);
                                 from.setDate(from.getDate() - 6);
                                 break;
                             }
                             case 'this_month':
                                 from = new Date(now.getFullYear(), now.getMonth(), 1);
                                 to = new Date();
                                 break;
                             case 'last_month':
                                 from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                                 to = new Date(now.getFullYear(), now.getMonth(), 0);
                                 break;
                             case 'this_quarter': {
                                 const q = Math.floor(now.getMonth() / 3);
                                 from = new Date(now.getFullYear(), q * 3, 1);
                                 to = new Date();
                                 break;
                             }
                             case 'last_quarter': {
                                 const q = Math.floor(now.getMonth() / 3);
                                 from = new Date(now.getFullYear(), (q - 1) * 3, 1);
                                 to = new Date(now.getFullYear(), q * 3, 0);
                                 break;
                             }
                             case 'this_year':
                                 from = new Date(now.getFullYear(), 0, 1);
                                 to = new Date();
                                 break;
                             case 'last_year':
                                 from = new Date(now.getFullYear() - 1, 0, 1);
                                 to = new Date(now.getFullYear() - 1, 11, 31);
                                 break;
                         }
                         this.fp.setDate([from, to], true);
                     }
                 }"
                 wire:ignore>
                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.created_at') }}</label>
                <div class="input-icon">
                    <span class="input-icon-addon">
                        {!! tabler_icon('calendar', ['class' => 'icon']) !!}
                    </span>
                    <input
                           x-ref="datePicker"
                           type="text"
                           class="form-control"
                           wire:model.live="search.date"
                           placeholder="{{ trans('modules/accounting::accounting.select_date_range') }}">
                </div>
                <div class="d-flex mt-2 flex-wrap gap-1">
                    @foreach ([
        'today' => 'Hôm nay',
        'yesterday' => 'Hôm qua',
        'this_week' => 'Tuần này',
        'last_week' => 'Tuần trước',
        'this_month' => 'Tháng này',
        'last_month' => 'Tháng trước',
        'this_quarter' => 'Quý này',
        'last_quarter' => 'Quý trước',
        'this_year' => 'Năm này',
        'last_year' => 'Năm trước',
    ] as $key => $label)
                        <button
                                type="button"
                                class="btn btn-sm"
                                :class="activePreset === '{{ $key }}' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="setPreset('{{ $key }}')">
                            {{ __($label) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- 3. Filter by Sale Channel (Kênh bán) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ __('Kênh bán') }}</label>
                <select class="form-select" wire:model.live="search.sale_channel_id">
                    <option value="">{{ __('core/base::general.all') }}</option>
                    @foreach ($saleChannels as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 4. Filter by Delivery Partner (Đối tác giao hàng) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.delivery_partner') }}</label>
                <select class="form-select" wire:model.live="search.delivery_partner_id">
                    <option value="">{{ __('core/base::general.all') }}</option>
                    @foreach ($deliveryPartners as $partner)
                        <option value="{{ $partner->id }}">
                            {{ $partner->code ? '[' . $partner->code . '] ' : '' }}{{ $partner->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 5. Filter by Order Type (Loại đơn: Đã thu tiền / Còn cần thu) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ __('Loại đơn hàng') }}</label>
                <select class="form-select" wire:model.live="search.order_type">
                    @foreach ($orderTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 6. Filter by Payment Method (Phương thức thanh toán) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.payment_method') }}</label>
                <select class="form-select" wire:model.live="search.type_payment_method">
                    <option value="">{{ __('core/base::general.all') }}</option>
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method['code'] }}">{{ $method['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 7. Filter by Customer (Khách hàng) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.customer') }}</label>

                @if (!empty($search['customer_id']) && !empty($customerResults))
                    <div class="input-group" wire:key="selected-customer-{{ $search['customer_id'] }}">
                        <input type="text" class="form-control" readonly
                               value="{{ data_get($customerResults[0], 'code') ? '[' . data_get($customerResults[0], 'code') . '] ' : '' }}{{ data_get($customerResults[0], 'name') }}" />
                        <button class="btn btn-outline-danger" wire:click="selectCustomer(null)" type="button">
                            {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                        </button>
                    </div>
                @else
                    <div class="input-icon">
                        <input
                               type="text"
                               class="form-control"
                               wire:model.live.debounce.300ms="customerSearch"
                               placeholder="{{ trans('modules/accounting::accounting.search_customer') }}">
                        <span class="input-icon-addon">{!! tabler_icon('search', ['class' => 'icon icon-sm']) !!}</span>
                    </div>

                    @if (count($customerResults) > 0)
                        <div class="card card-sm mt-2">
                            <div class="list-group list-group-flush">
                                @foreach ($customerResults as $customer)
                                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                       wire:key="customer-search-{{ $customer->id }}"
                                       wire:click.prevent="selectCustomer({{ $customer->id }})">
                                        <span>
                                            @if ($customer->code)
                                                <span class="text-muted small">[{{ $customer->code }}]</span>
                                            @endif
                                            {{ $customer->name }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @elseif(strlen($customerSearch) >= 2)
                        <div class="text-muted small mt-2">
                            {{ trans('modules/accounting::accounting.customer_not_found') }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- 8. Filter by User (Nhân viên bán hàng) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ trans('modules/accounting::accounting.sales_person') }}</label>
                <select class="form-select" wire:model.live="search.user_id">
                    <option value="">{{ __('core/base::general.all') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 9. Filter by Status (Trạng thái) --}}
            <div class="mb-3">
                <label class="form-label small text-muted">{{ __('modules/accounting::accounting.filter_by_status') }}</label>
                <div class="d-flex flex-column gap-1">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input"
                               wire:click="toggleAllStatuses"
                               @checked(count($statusChecked) === count($statuses))>
                        <span class="form-check-label fw-semibold">{{ __('core/base::general.all') }}</span>
                    </label>
                    @foreach ($statuses as $key => $label)
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   wire:click="toggleStatus('{{ $key }}')"
                                   @checked(in_array($key, $statusChecked))>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Active filter indicator --}}
            @if (!empty($search['code']) || !empty($search['status']) || !empty($search['user_id']) || !empty($search['date']) || !empty($search['sale_channel_id']) || !empty($search['customer_id']) || !empty($search['order_type']) || !empty($search['delivery_partner_id']) || !empty($search['type_payment_method']) || !empty($search['product_search']))
                <div class="bg-primary-lt d-flex align-items-center justify-content-between rounded p-2">
                    <span class="small text-primary">
                        {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                        {{ __('core/base::general.filter_active') }}
                    </span>
                    <button
                            class="btn btn-sm btn-ghost-danger btn-icon"
                            wire:click="clearFilter"
                            title="{{ __('core/base::general.clear_filter') }}">
                        {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                    </button>
                </div>
            @endif
        </div>
    </x-ui::card>

    {{-- Quick Actions Card --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center mb-2 gap-2">
            {!! tabler_icon('bolt', ['class' => 'icon text-warning']) !!}
            <span class="fw-semibold">{{ __('core/base::general.quick_actions') }}</span>
        </div>

        <div class="d-grid gap-2">
            @can('accountings.create')
                <button
                        type="button"
                        class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2"
                        @click="$dispatch('show-modal-create-sale-invoice')">
                    {!! tabler_icon('shopping-cart', ['class' => 'icon']) !!}
                    {{ __('modules/accounting::accounting.create_invoice') }}
                </button>
            @endcan
            <button
                    type="button"
                    class="btn btn-outline-success d-flex align-items-center justify-content-start gap-2"
                    @click="$dispatch('show-modal-create-customer', { id: 0 })">
                {!! tabler_icon('user-plus', ['class' => 'icon']) !!}
                {{ trans('modules/accounting::accounting.add_customer') }}
            </button>
            <button
                    type="button"
                    class="btn btn-outline-info d-flex align-items-center justify-content-start gap-2"
                    @click="$dispatch('show-modal-create-sale-channel')">
                {!! tabler_icon('building-store', ['class' => 'icon']) !!}
                {{ trans('modules/accounting::accounting.add_sale_channel') }}
            </button>
            <button
                    type="button"
                    class="btn btn-outline-warning d-flex align-items-center justify-content-start gap-2"
                    @click="$dispatch('show-modal-create-partner-delivery')">
                {!! tabler_icon('truck', ['class' => 'icon']) !!}
                {{ trans('modules/accounting::accounting.add_delivery_partner') }}
            </button>
            <button
                    type="button"
                    class="btn btn-outline-secondary d-flex align-items-center justify-content-start gap-2"
                    @click="$dispatch('modal-create-payment-method')">
                {!! tabler_icon('credit-card', ['class' => 'icon']) !!}
                {{ trans('modules/accounting::accounting.add_payment_method_filter') }}
            </button>
        </div>
    </x-ui::card>
</div>
