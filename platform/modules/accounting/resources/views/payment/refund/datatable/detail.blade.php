@php
    $totalProducts = $row->products->count() ?? 0;
    $refundAmount = ($row->value ?? 0) / 100;
    $originalAmount = ($row->original_total_amount ?? 0) / 100;
@endphp

<div>
    <x-ui::card>
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning-lt text-warning">
                        {!! tabler_icon('rotate', ['class' => 'icon icon-sm text-warning']) !!}
                        Trả hàng
                    </span>
                    <span class="badge bg-muted-lt">
                        {{ $row->code }}
                    </span>
                </div>
                <p class="text-muted small mb-0">
                    {!! tabler_icon('shopping-cart', ['class' => 'icon icon-sm text-muted me-1']) !!}
                    Đơn gốc: <strong>{{ $row->payment?->code ?? 'N/A' }}</strong>
                </p>
            </div>
            <div class="text-end">
                <div class="h2 mb-0 text-warning">{{ core_number_format($refundAmount) }} đ</div>
                <div class="text-muted small">Tiền trả</div>
            </div>
        </div>

        {{-- Info Cards --}}
        <div class="row g-3 mb-4">
            {{-- Customer Info --}}
            <div class="col-md-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-primary-lt me-2">
                                {!! tabler_icon('user', ['class' => 'icon icon-sm text-primary']) !!}
                            </span>
                            <div class="subheader">Khách hàng</div>
                        </div>
                        <div class="h5 mb-1">{{ $row->payment?->customer?->name ?? 'Khách lẻ' }}</div>
                        @if($row->payment?->customer?->phone)
                            <div class="text-muted small">
                                {!! tabler_icon('phone', ['class' => 'icon icon-sm text-muted me-1']) !!}
                                {{ $row->payment->customer->phone }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Amount Info --}}
            <div class="col-md-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-info-lt me-2">
                                {!! tabler_icon('receipt-2', ['class' => 'icon icon-sm text-info']) !!}
                            </span>
                            <div class="subheader">Số tiền</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">Gốc</div>
                                <div class="h6 mb-0">{{ core_number_format($originalAmount) }} đ</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">Trả</div>
                                <div class="h5 mb-0 text-warning">{{ core_number_format($refundAmount) }} đ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Products & Fees --}}
            <div class="col-md-12 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar bg-success-lt me-2">
                                {!! tabler_icon('package', ['class' => 'icon icon-sm text-success']) !!}
                            </span>
                            <div class="subheader">Chi tiết</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-muted small">Số lượng SP</div>
                                <div class="h6 mb-0">{{ $row->amount ?? 0 }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Sau giảm</div>
                                <div class="h6 mb-0">{{ core_number_format(($row->total_payable_amount ?? 0) / 100) }} đ</div>
                            </div>
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
                        <td class="text-muted" style="width: 45%;">{{ trans('modules/accounting::accounting.created_at') }}</td>
                        <td><strong>{{ core_format_date($row->created_at) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ trans('modules/accounting::accounting.created_by') }}</td>
                        <td><strong>{{ $row->creator?->name ?? 'N/A' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ trans('modules/accounting::accounting.handler') }}</td>
                        <td><strong>{{ $row->user?->name ?? 'N/A' }}</strong></td>
                    </tr>
                </x-ui::table>
            </div>
            <div class="col-md-6">
                @if(($row->discount_value ?? 0) > 0 || ($row->refund_fee ?? 0) > 0 || $row->note)
                    <div class="card card-sm bg-muted-lt">
                        <div class="card-body py-2">
                            @if(($row->discount_value ?? 0) > 0)
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Giảm giá:</span>
                                    <span>
                                        {{ core_number_format(($row->discount_value ?? 0) / 100) }}
                                        {{ ($row->discount_type ?? 'number') === 'percent' ? '%' : 'đ' }}
                                    </span>
                                </div>
                            @endif
                            @if(($row->refund_fee ?? 0) > 0)
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Phí trả hàng:</span>
                                    <span>
                                        {{ core_number_format(($row->refund_fee ?? 0) / 100) }}
                                        {{ ($row->refund_fee_type ?? 'number') === 'percent' ? '%' : 'đ' }}
                                    </span>
                                </div>
                            @endif
                            @if($row->note)
                                <div class="small mt-2 pt-2 border-top">
                                    <span class="text-muted">Ghi chú:</span>
                                    <div>{{ $row->note }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <hr>

        {{-- Products Table --}}
        <h5 class="mb-3">{{ trans('modules/accounting::accounting.product_return') }}</h5>
        <x-ui::table striped class="card-table">
            <thead>
                <tr>
                    <th class="w-1">{{ trans('modules/accounting::accounting.order_no') }}</th>
                    <th>{{ trans('modules/accounting::accounting.product_code') }}</th>
                    <th>{{ trans('modules/accounting::accounting.product_name') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.quantity') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.unit_price') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.discount') }}</th>
                    <th class="text-end">{{ trans('modules/accounting::accounting.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($row->products as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><span class="badge bg-muted-lt">{{ $item->product?->code ?? 'N/A' }}</span></td>
                        <td><strong>{{ $item->product?->name ?? 'N/A' }}</strong></td>
                        <td class="text-end">{{ core_number_format($item->amount) }}</td>
                        <td class="text-end">{{ core_number_format(($item->price ?? 0) / 100) }}</td>
                        <td class="text-end">
                            @if(($item->discount_value ?? 0) > 0)
                                <span class="text-danger">-{{ core_number_format(($item->discount_value ?? 0) / 100) }}</span>
                                {{ ($item->discount_type ?? 'number') === 'percent' ? '%' : 'đ' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end"><strong>{{ core_number_format(($item->value ?? 0) / 100) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <span class="text-muted">
                                {!! tabler_icon('box', ['class' => 'icon icon-md text-muted mb-2']) !!}
                                <br>{{ trans('Không có sản phẩm') }}
                            </span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui::table>
    </x-ui::card>
</div>
