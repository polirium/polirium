<div>
    {{-- Filter Panel --}}
    <x-ui::card>
        {{-- Header với icon --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                {!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}
                <span class="fw-semibold">{{ __('core/base::general.filter') }}</span>
            </div>
        </div>

        {{-- Search by code --}}
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
                    placeholder="{{ __('core/base::general.search_placeholder') }}"
                >
            </div>
        </div>

        {{-- Filter by Date --}}
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
                     switch(key) {
                         case 'today':
                             from = to = new Date(now);
                             break;
                         case 'yesterday': {
                             const d = new Date(now); d.setDate(d.getDate() - 1);
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
                             to = new Date(mon); to.setDate(to.getDate() - 1);
                             from = new Date(to); from.setDate(from.getDate() - 6);
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
             wire:ignore
        >
            <label class="form-label small text-muted">{{ __('Ngày tạo') }}</label>
            <div class="input-icon">
                <span class="input-icon-addon">
                    {!! tabler_icon('calendar', ['class' => 'icon']) !!}
                </span>
                <input
                    x-ref="datePicker"
                    type="text"
                    class="form-control"
                    wire:model.live="search.date"
                    placeholder="{{ __('Chọn khoảng thời gian') }}"
                >
            </div>
            <div class="d-flex flex-wrap gap-1 mt-2">
                @foreach([
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
                        @click="setPreset('{{ $key }}')"
                    >
                        {{ __($label) }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Filter by Status --}}
        <div class="mb-3">
            <label class="form-label small text-muted">{{ __('modules/accounting::accounting.filter_by_status') }}</label>
            <select class="form-select" wire:model.live="search.status">
                <option value="">{{ __('core/base::general.all') }}</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Active filter indicator --}}
        @if (!empty($search['code']) || !empty($search['status']) || !empty($search['date']))
            <div class="p-2 bg-primary-lt rounded d-flex align-items-center justify-content-between">
                <span class="small text-primary">
                    {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                    {{ __('core/base::general.filter_active') }}
                </span>
                <button
                    class="btn btn-sm btn-ghost-danger btn-icon"
                    wire:click="clearFilter"
                    title="{{ __('core/base::general.clear_filter') }}"
                >
                    {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                </button>
            </div>
        @endif
    </x-ui::card>

    {{-- Quick Actions Card --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            {!! tabler_icon('bolt', ['class' => 'icon text-warning']) !!}
            <span class="fw-semibold">{{ __('core/base::general.quick_actions') }}</span>
        </div>

        <div class="d-grid gap-2">
            @can('vendors.purchases.create')
            <a
                href="{{ route('vendors.purchases.order') }}"
                class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2"
            >
                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                {{ __('modules/vendor::purchase.create') }}
            </a>
            <button
                class="btn btn-outline-secondary d-flex align-items-center justify-content-start gap-2"
                wire:click="$dispatch('show-modal-import-purchase')"
            >
                {!! tabler_icon('file-import', ['class' => 'icon']) !!}
                {{ __('core/base::general.import_excel') }}
            </button>
            @endcan
        </div>
    </x-ui::card>
</div>
