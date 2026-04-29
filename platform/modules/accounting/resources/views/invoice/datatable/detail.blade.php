@php
    $totalAmount = $row->products->sum('total') ?? 0;
    $statusColor = match(($row->finance?->status ?? $row->status ?? '')) {
        'paid', 'completed' => 'success',
        'pending', 'processing' => 'warning',
        'cancelled', 'failed' => 'danger',
        default => 'secondary'
    };
@endphp

<div>
    <x-ui::card>
        {{-- Header with code and status --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <p class="h3 mb-1"><b>{{ $row->code }}</b></p>
                <p class="text-muted small mb-0">
                    {!! tabler_icon('calendar', ['class' => 'icon icon-sm text-muted me-1']) !!}
                    {{ core_format_date($row->date ?? $row->created_at) }}
                </p>
            </div>
            <span class="badge bg-{{ $statusColor }}-lt text-{{ $statusColor }} fs-6">
                {{ $row->finance?->status ?? ($row->status ?? '-') }}
            </span>
        </div>

        {{-- Info Cards Grid --}}
        <div class="row g-3 mb-4">
            {{-- Customer Info --}}
            <div class="col-md-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-primary-lt me-2">
                                {!! tabler_icon('user', ['class' => 'text-primary icon']) !!}
                            </span>
                            <div class="subheader">{{ trans('modules/accounting::accounting.customer') }}</div>
                        </div>
                        <div class="h4 mb-0">{{ $row->finance?->customer?->name ?? trans('modules/accounting::accounting.retail_customer') }}</div>
                        <div class="text-muted small mt-1">
                            {{ $row->branch?->name ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Info --}}
            <div class="col-md-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-success-lt me-2">
                                {!! tabler_icon('credit-card', ['class' => 'text-success icon']) !!}
                            </span>
                            <div class="subheader">{{ trans('modules/accounting::accounting.payment_amount') }}</div>
                        </div>
                        <div class="h4 mb-0 text-success">
                            {{ core_number_format($row->finance->value_payment ?? ($row->value ?? 0)) }} đ
                        </div>
                        @if(($row->finance->discount_value ?? 0) > 0)
                            <div class="text-muted small mt-1">
                                {{ trans('modules/accounting::accounting.discount') }}: {{ core_number_format($row->finance->discount_value ?? 0) }}
                                {{ in_array(($row->finance->discount_type ?? 'number'), ['%', 'percent']) ? '%' : 'đ' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Total Info --}}
            <div class="col-md-12 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-info-lt me-2">
                                {!! tabler_icon('receipt-2', ['class' => 'text-info icon']) !!}
                            </span>
                            <div class="subheader">{{ trans('modules/accounting::accounting.total_amount') }}</div>
                        </div>
                        <div class="h3 mb-0 text-info">
                            {{ core_number_format($row->value ?? $totalAmount) }} đ
                        </div>
                        <div class="text-muted small mt-1">
                            {{ trans('modules/accounting::accounting.total_cost') }}: {{ core_number_format($row->finance->total_cost ?? 0) }} đ
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <x-ui::table class="table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">{{ trans('modules/accounting::accounting.user') }}</td>
                        <td><strong>{{ $row->finance->user?->name ?? $row->user?->name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ trans('modules/accounting::accounting.branch') }}</td>
                        <td><strong>{{ $row->branch?->name ?? 'N/A' }}</strong></td>
                    </tr>
                </x-ui::table>
            </div>
            <div class="col-md-6">
                <div class="card card-sm bg-muted-lt">
                    <div class="card-body py-2">
                        <div class="text-muted small mb-1">{{ trans('core/base::general.note') }}</div>
                        @if($editingNoteId === $row->id)
                            <textarea wire:model="noteContent" class="form-control" rows="3"></textarea>
                        @else
                            <div class="small">{{ $row->note ?? 'Không có ghi chú' }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <hr>

        {{-- Products Table --}}
        <h5 class="mb-3">{{ trans('modules/accounting::accounting.product_detail') }}</h5>
        <x-ui::table striped class="card-table">
            <thead>
                <tr>
                    <th class="w-1">{{ trans('modules/accounting::accounting.order_no') }}</th>
                    <th>{{ trans('modules/product::product.code') }}</th>
                    <th>{{ trans('modules/product::product.name') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.quantity') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.unit_price') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.discount') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.total') }}</th>
                    <th>{{ trans('core/base::general.note') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($row->products as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><span class="badge bg-muted-lt">{{ $item['product']['code'] ?? '-' }}</span></td>
                        <td><strong>{{ $item['product']['name'] ?? '-' }}</strong></td>
                        <td class="text-end">{{ core_number_format($item['amount'] ?? 0) }}</td>
                        <td class="text-end">{{ core_number_format($item['value'] ?? 0) }}</td>
                        <td class="text-end">
                            @if(($item['discount_value'] ?? 0) > 0)
                                <span class="text-danger">-{{ core_number_format($item['discount_value'] ?? 0) }}</span>
                                {{ in_array(($item['discount_type'] ?? 'percent'), ['%', 'percent']) ? '%' : 'đ' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end"><strong>{{ core_number_format($item['total'] ?? 0) }}</strong></td>
                        <td><span class="text-muted small">{{ $item['note'] ?? '-' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <span class="text-muted">
                                {!! tabler_icon('box', ['class' => 'icon icon-md text-muted mb-2']) !!}
                                <br>{{ trans('Không có sản phẩm') }}
                            </span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($row->products->count() > 0)
                <tfoot>
                    <tr class="table-primary">
                        <td colspan="6" class="text-end"><strong>Tổng cộng:</strong></td>
                        <td class="text-end"><strong class="h5 mb-0">{{ core_number_format($totalAmount) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </x-ui::table>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <div class="d-flex gap-2">
                {{-- Cancel Button --}}
                @can('accountings.cancel')
                    @if(!in_array($row->status, ['cancelled']))
                    <x-ui::button
                        color="white"
                        size="sm"
                        icon="trash"
                        class="text-danger"
                        wire:click="cancel({{ $row->id }})"
                        wire:confirm="Bạn có chắc chắn muốn xóa đơn này? Thao tác này sẽ xóa đơn và cập nhật lại tồn kho (nếu đã nhập)."
                    >
                        {{ trans('modules/accounting::accounting.cancel') }}
                    </x-ui::button>
                    @endif
                @endcan

                 {{-- Copy Button --}}
                <x-ui::button
                    color="white"
                    size="sm"
                    icon="copy"
                    :href="route('accountings.payment.copy', $row->id)"
                >
                    {{ trans('modules/accounting::accounting.copy') }}
                </x-ui::button>

                {{-- Export Button --}}
                <x-ui::button
                    color="white"
                    size="sm"
                    icon="file-export"
                    :href="route('accountings.payment.export', $row->id)"
                    target="_blank"
                >
                    {{ trans('modules/accounting::accounting.export_file') }}
                </x-ui::button>
            </div>

            <div class="d-flex gap-2">
                 {{-- Edit Note Trigger --}}
                 @if($editingNoteId !== $row->id)
                    <x-ui::button
                        color="primary"
                        size="sm"
                        icon="pencil"
                        wire:click="editNote({{ $row->id }}, '{{ $row->note }}')"
                    >
                        {{ trans('modules/accounting::accounting.edit') }}
                    </x-ui::button>
                 @else
                    <x-ui::button
                        color="success"
                        size="sm"
                        icon="device-floppy"
                        wire:click="saveNote"
                    >
                        {{ trans('modules/accounting::accounting.save') }}
                    </x-ui::button>
                     <x-ui::button
                        color="secondary"
                        size="sm"
                        icon="x"
                        wire:click="cancelEdit"
                    >
                        {{ trans('modules/accounting::accounting.close') }}
                    </x-ui::button>
                 @endif

                {{-- Return Button --}}
                <x-ui::button
                    color="white"
                    size="sm"
                    icon="arrow-back-up"
                     href="{{ route('products.payment.refund', $id) }}"
                >
                    {{ trans('modules/accounting::accounting.refund') }}
                </x-ui::button>

                 {{-- Print Button --}}
                <x-ui::button
                    color="white"
                    size="sm"
                    icon="printer"
                    :href="route('products.print.print-payment', $row->id)"
                    target="_blank"
                >
                    {{ __('In') }}
                </x-ui::button>
            </div>
        </div>
    </x-ui::card>
</div>
