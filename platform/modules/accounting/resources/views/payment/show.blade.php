@extends('core/ui::base.base')

@section('title', __('Chi tiết hóa đơn #:code', ['code' => $payment->code]))

@section('content_header')
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">{{ __('Chi tiết hóa đơn #:code', ['code' => $payment->code]) }}</h2>
            <div class="text-muted mt-1">
                {{ $payment->created_at->format('d/m/Y H:i') }} | {{ $payment->user->name ?? '-' }}
            </div>
        </div>
        <div class="d-print-none col-auto ms-auto">
            <div class="btn-list">
                @if (auth()->user()->can('accountings.edit'))
                    <x-ui::button
                                  color="warning"
                                  icon="pencil"
                                  onclick="window.Livewire.dispatch('show-modal-create-sale-invoice', { id: {{ $payment->id }} })">
                        {{ trans('modules/accounting::accounting.edit') }}
                    </x-ui::button>
                @endif
                @can('accountings.refunds')
                    @if ($payment->status === 'success')
                        <a href="{{ route('products.payment.refund', $payment->id) }}" class="btn btn-danger">
                            {!! tabler_icon('arrow-back-up') !!} {{ trans('modules/accounting::accounting.refund') }}
                        </a>
                    @endif
                @endcan
                @can('accountings.create')
                    <a href="{{ route('accountings.payment.copy', $payment->id) }}" class="btn btn-secondary" onclick="return confirm('{{ trans('modules/accounting::accounting.confirm_copy_invoice') }}')">
                        {!! tabler_icon('copy') !!} {{ trans('modules/accounting::accounting.copy') }}
                    </a>
                @endcan
                <button type="button" onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', $payment->id) }}')" class="btn btn-primary">
                    {!! tabler_icon('printer') !!} {{ trans('modules/accounting::accounting.print') }}
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    {{-- Tabs Navigation --}}
    <div class="card mb-3">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-info" class="nav-link active" data-bs-toggle="tab">{{ trans('modules/accounting::accounting.general_info') }}</a>
                </li>
                <li class="nav-item">
                    <a href="#tab-history" class="nav-link" data-bs-toggle="tab">{{ __('Lịch sử thanh toán') }}</a>
                </li>
                <li class="nav-item">
                    <a href="#tab-returns" class="nav-link" data-bs-toggle="tab">{{ __('Lịch sử trả hàng') }}</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- TAB: Info --}}
                <div class="tab-pane active show" id="tab-info">
                    <div class="row row-cards">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ trans('modules/accounting::accounting.product_list') }}</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table-vcenter card-table table-striped table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{ trans('modules/accounting::accounting.product') }}</th>
                                                <th class="text-center">{{ __('SL') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.unit_price') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.discount') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($payment->products as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <div class="font-weight-medium">{{ $item->product->name ?? $item->product_name }}</div>
                                                        @if ($item->product)
                                                            <a href="{{ route('products.index', ['code' => $item->product->code]) }}" class="small text-primary" target="_blank">{{ $item->product->code }}</a>
                                                        @else
                                                            <div class="text-muted small">-</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $item->amount }}</td>
                                                    <td class="text-end">{{ number_format($item->price) }}</td>
                                                    <td class="text-danger text-end">
                                                        @if ($item->discount_value > 0)
                                                            @php
                                                                $itemDiscountAmount = $item->discount_value;
                                                                if ($item->discount_type === 'percent') {
                                                                    $itemDiscountAmount = ($item->price * $item->discount_value) / 100;
                                                                }
                                                            @endphp
                                                            -{{ number_format($itemDiscountAmount) }}
                                                            @if ($item->discount_type === 'percent')
                                                                ({{ $item->discount_value }}%)
                                                            @endif
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="fw-bold text-end">{{ number_format($item->value) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-end">{{ trans('modules/accounting::accounting.total_goods') }}</td>
                                                <td class="text-end">{{ number_format($payment->total_cost) }}</td>
                                            </tr>
                                            @if ($payment->discount_value > 0)
                                                @php
                                                    $invoiceDiscountAmount = $payment->discount_type === 'percent' ? ($payment->total_cost * $payment->discount_value) / 100 : $payment->discount_value;
                                                @endphp
                                                <tr>
                                                    <td colspan="5" class="text-end">{{ trans('modules/accounting::accounting.invoice_discount') }}@if ($payment->discount_type === 'percent')
                                                            ({{ $payment->discount_value }}%)
                                                        @endif
                                                    </td>
                                                    <td class="text-danger text-end">-{{ number_format($invoiceDiscountAmount) }}</td>
                                                </tr>
                                            @endif
                                            @if ($payment->extra_fee > 0)
                                                <tr>
                                                    <td colspan="5" class="text-end">{{ trans('modules/accounting::accounting.other_receipt') }}</td>
                                                    <td class="text-end">{{ number_format($payment->extra_fee) }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td colspan="5" class="font-weight-bold text-end">{{ trans('modules/accounting::accounting.customer_refundable') }}</td>
                                                <td class="font-weight-bold text-primary text-end">{{ number_format($payment->value) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-end">{{ trans('modules/accounting::accounting.customer_paid') }}</td>
                                                <td class="text-end">{{ number_format($payment->value_payment) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header h3">{{ trans('modules/accounting::accounting.customer_info') }}</div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.customer_label') }}</strong>
                                        <a href="{{ route('customers.index') }}" class="text-primary float-end">{{ $payment->customer->name ?? 'Khách lẻ' }}</a>
                                    </div>
                                    @if ($payment->customer)
                                        <div class="mb-2">
                                            <strong>{{ trans('modules/accounting::accounting.customer_code_label') }}</strong>
                                            <a href="{{ route('customers.index') }}" class="text-primary float-end">{{ $payment->customer->code ?? '-' }}</a>
                                        </div>
                                    @endif
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.phone_label') }}</strong>
                                        <span class="float-end">{{ $payment->customer->phone ?? '-' }}</span>
                                    </div>
                                    @if ($payment->customer?->address)
                                        <div class="mb-2">
                                            <strong>{{ trans('modules/accounting::accounting.address_label') }}</strong>
                                            <span class="float-end">{{ $payment->customer->address }}</span>
                                        </div>
                                    @endif
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.customer_group_label') }}</strong>
                                        <span class="float-end">{{ $payment->customer->group->name ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header h3">{{ trans('modules/accounting::accounting.other_info') }}</div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.invoice_code_label') }}</strong>
                                        <span class="float-end">{{ $payment->code }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.status_label') }}</strong>
                                        <span class="badge bg-{{ $payment->status === 'success' ? 'success' : 'warning' }} float-end">
                                            {{ trans('modules/product::product.status.' . $payment->status) }}
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.branch_label') }}</strong>
                                        <span class="float-end">{{ $payment->branch->name ?? '-' }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>{{ trans('modules/accounting::accounting.note_label') }}</strong>
                                        <p class="text-muted small mt-1">{{ $payment->note ?? 'Không có' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: History --}}
                <div class="tab-pane" id="tab-history">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted py-4 text-center">
                                {{ trans('modules/accounting::accounting.feature_developing') }}
                                {{-- @livewire('modules/accounting::payment.history', ['payment_id' => $payment->id]) --}}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- TAB: Returns --}}
                <div class="tab-pane" id="tab-returns">
                    <div class="card">
                        <div class="card-body">
                            @if ($payment->refunds->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table-vcenter card-table table-striped table">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('modules/accounting::accounting.voucher_code') }}</th>
                                                <th>{{ trans('modules/accounting::accounting.created_at') }}</th>
                                                <th>{{ trans('modules/accounting::accounting.created_by') }}</th>
                                                <th class="text-end">{{ trans('modules/accounting::accounting.total_refund') }}</th>
                                                <th class="text-end"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($payment->refunds as $refund)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('products.payment.v2', ['refund_id' => $refund->id]) }}" class="fw-bold text-reset">
                                                            {{ $refund->code }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $refund->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $refund->user->name ?? '-' }}</td>
                                                    <td class="fw-bold text-danger text-end">{{ number_format($refund->value / 100) }}</td>
                                                    <td class="text-end">
                                                        <button type="button" onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', $refund->id) }}')" class="btn btn-sm btn-icon btn-ghost-secondary">
                                                            {!! tabler_icon('printer') !!}
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted py-4 text-center">
                                    {{ trans('modules/accounting::accounting.no_refund_voucher') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @livewire('modules/accounting::payment.modal.modal-quick-update-component')
@endpush
