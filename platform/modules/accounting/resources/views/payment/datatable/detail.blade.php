@php
    // Ensure relationships are loaded
    $payment = \Polirium\Modules\Product\Http\Model\Payment\Payment::with(['products.product', 'customer', 'user', 'author', 'branch', 'saleChannel', 'refunds.user', 'latestDelivery.partnerDelivery'])->find($row->id);
    $refunds = $payment->refunds ?? collect();
    $paymentHistory = $payment->paymentHistory ?? collect();
@endphp

<style>
    .invoice-detail-selectable,
    .invoice-detail-selectable * {
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        cursor: text !important;
    }
</style>

<div class="p-3 shadow-sm border rounded invoice-detail-selectable"
    style="position: sticky; left: 0; background: var(--tblr-bg-surface); z-index: 10;"
    x-data="{
        activeTab: 'info',
        initWidth() {
            const container = this.$el.closest('.table-responsive');
            if(container) {
                this.$el.style.width = container.clientWidth + 'px';
            }
        }
    }"
    x-init="
        initWidth();
        window.addEventListener('resize', () => initWidth());
        Livewire.hook('morph.updated', ({el}) => {
            if ($el === el || $el.contains(el) || el.contains($el)) {
                $nextTick(() => initWidth());
            }
        });
    ">
    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a href="#" class="nav-link" :class="{ 'active': activeTab === 'info' }" @click.prevent="activeTab = 'info'">
                {{ trans('modules/accounting::accounting.information') }}
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" :class="{ 'active': activeTab === 'history' }" @click.prevent="activeTab = 'history'">
                {{ __('Lịch sử thanh toán') }}
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" :class="{ 'active': activeTab === 'returns' }" @click.prevent="activeTab = 'returns'">
                {{ __('Lịch sử trả hàng') }}
            </a>
        </li>
    </ul>

    {{-- Tab: Thông tin --}}
    <div x-show="activeTab === 'info'" x-cloak>
        {{-- Header Info --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <span class="fw-bold">{{ $payment->customer?->name ?? trans('modules/accounting::accounting.walk_in') }}</span>
                <span class="badge bg-blue-lt ms-2">{{ $payment->code }}</span>
                @php
                    $statusText = match($payment->status) {
                        'success' => 'Hoàn thành',
                        'temp' => 'Tạm',
                        'pending' => 'Chờ xử lý',
                        'cancelled' => 'Đã hủy',
                        default => $payment->status
                    };
                    $statusColor = match($payment->status) {
                        'success' => 'green',
                        'temp' => 'yellow',
                        'pending' => 'blue',
                        'cancelled' => 'red',
                        default => 'secondary'
                    };
                @endphp
                <span class="badge bg-{{ $statusColor }}-lt text-{{ $statusColor }}">{{ $statusText }}</span>
            </div>
            <div class="text-muted small">{{ $payment->branch?->name ?? '-' }}</div>
        </div>

        {{-- Meta Info Row --}}
        <div class="row mb-3 small">
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.created_by_label') }}</span>
                <span class="fw-medium">{{ $payment->author?->name ?? ($payment->user?->name ?? '-') }}</span>
            </div>
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.sale_date') }}</span>
                <span class="fw-medium">
                    @if($payment->created_at)
                        {{ $payment->created_at->format('d/m/Y H:i') }}
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="col-auto">
                <span class="text-muted">{{ __('Kênh bán:') }}</span>
                <span class="fw-medium">{{ $payment->saleChannel?->name ?? trans('modules/accounting::accounting.direct_sale') }}</span>
            </div>
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.partner_label') }}</span>
                <span class="fw-medium">{{ $payment->latestDelivery?->partnerDelivery?->name ?? '-' }}</span>
            </div>
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.delivery_code_label') }}</span>
                <span class="fw-medium">{{ $payment->latestDelivery?->code ?? '-' }}</span>
            </div>
            <div class="col-auto ms-auto border-start ps-3">
                <div class="mb-1">
                    <span class="text-muted">{{ trans('modules/accounting::accounting.price_list') }}</span>
                    <span class="fw-medium text-primary">{{ trans('modules/accounting::accounting.general_price') }}</span>
                </div>
                @php
                    $rawTypePayments = is_string($payment->type_payment) ? json_decode($payment->type_payment, true) : (is_array($payment->type_payment) ? $payment->type_payment : []);
                    $methods = [];
                    if (!empty($rawTypePayments) && is_array($rawTypePayments)) {
                        $paymentMethodCache = \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::pluck('name', 'code')->toArray();
                        foreach ($rawTypePayments as $tp) {
                            if (isset($tp['value']) && $tp['value'] > 0) {
                                $methodCode = $tp['method'] ?? '';
                                $methods[] = $tp['label'] ?? ($paymentMethodCache[$methodCode] ?? $methodCode);
                            }
                        }
                    }
                    // Fallback for old data without type_payment
                    if (empty($methods) && $payment->value_payment > 0) {
                        $methods[] = 'Tiền mặt';
                    }
                    $methodNames = empty($methods) ? 'Chưa thanh toán' : implode(' / ', array_unique($methods));
                @endphp
                <div>
                    <span class="text-muted">{{ __('Phương thức TT:') }}</span>
                    <span class="fw-medium text-primary">{{ $methodNames }}</span>
                </div>
            </div>
        </div>

        {{-- Customer Info Row --}}
        @if($payment->customer)
        <div class="row mb-3 small">
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.customer_code_label') }}</span>
                <a href="{{ route('customers.index', ['show_detail' => $payment->customer->id]) }}" class="fw-medium text-primary" title="{{ trans('modules/accounting::accounting.view_customer_detail') }}" target="_blank">
                    {{ $payment->customer->code ?? '-' }}
                </a>
            </div>
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.phone_label_with_colon') }}</span>
                <span class="fw-medium">{{ $payment->customer->phone ?? '-' }}</span>
            </div>
            <div class="col-auto">
                <span class="text-muted">{{ trans('modules/accounting::accounting.address_label') }}</span>
                <span class="fw-medium">{{ $payment->customer->address ?? '-' }}</span>
            </div>
        </div>
        @endif

        {{-- Product Table --}}
        <div class="table-responsive mb-3">
            <table class="table table-sm table-vcenter table-striped" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th style="width: 15%;">{{ trans('modules/accounting::accounting.product_code_full') }}</th>
                        <th style="width: 35%;">{{ trans('modules/accounting::accounting.product_name_short') }}</th>
                        <th class="text-end" style="width: 10%;">{{ trans('modules/accounting::accounting.quantity') }}</th>
                        <th class="text-end" style="width: 10%;">{{ trans('modules/accounting::accounting.unit_price') }}</th>
                        <th class="text-end" style="width: 10%;">{{ trans('modules/accounting::accounting.discount') }}</th>
                        <th class="text-end" style="width: 10%;">{{ trans('modules/accounting::accounting.selling_price') }}</th>
                        <th class="text-end" style="width: 10%;">{{ trans('modules/accounting::accounting.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                        @forelse ($payment->products as $product)
                                @php
                                $productCode = is_array($product) ? ($product['product']['code'] ?? '-') : ($product->product?->code ?? '-');
                                $productName = is_array($product) ? ($product['product']['name'] ?? 'Đã xóa') : ($product->product?->name ?? 'Đã xóa');
                                $amount = is_array($product) ? ($product['amount'] ?? 0) : ($product->amount ?? 0);
                                $originalPrice = is_array($product) ? ($product['value'] ?? 0) : ($product->value ?? 0);
                                $discountValue = is_array($product) ? ($product['discount_value'] ?? 0) : ($product->discount_value ?? 0);
                                $discountType = is_array($product) ? ($product['discount_type'] ?? 'number') : ($product->discount_type ?? 'number');
                                $total = is_array($product) ? ($product['total'] ?? 0) : ($product->total ?? 0);

                                // Discount tracking
                                $displayDiscount = $discountValue;
                                if ($discountType === 'percent' && $discountValue >= 100) {
                                    $displayDiscount = $originalPrice;
                                } elseif ($discountType === 'percent') {
                                    $displayDiscount = ($originalPrice * $discountValue) / 100;
                                }

                                // Giá bán = Original Price - Discount per item
                                $salePrice = $amount > 0 ? ($total / $amount) : 0;
                            @endphp
                            <tr>
                                <td><a href="{{ route('products.index', ['code' => $productCode]) }}" class="text-primary" target="_blank">{{ $productCode }}</a></td>
                                <td>{{ $productName }}</td>
                                <td class="text-end">{{ $amount }}</td>
                                <td class="text-end">{{ core_number_format($originalPrice) }}</td>
                                <td class="text-end text-danger">{{ $displayDiscount > 0 ? core_number_format($displayDiscount) : '' }}</td>
                                <td class="text-end">{{ core_number_format($salePrice) }}</td>
                                <td class="text-end fw-bold">{{ core_number_format($total) }}</td>
                            </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center py-4">{{ __('Không có sản phẩm') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Note and Summary Row --}}
        <div class="row">
            <div class="col-md-6">
                <div class="border rounded p-2 bg-light small">
                    <span class="text-muted">{{ trans('modules/accounting::accounting.note_placeholder') }}</span>
                    @livewire('modules/accounting::payment.payment-note', ['payment' => $payment], key('note-' . $payment->id))
                </div>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-end text-muted">{{ trans('modules/accounting::accounting.total_goods') }} ({{ count($payment->products) }})</td>
                        <td class="text-end fw-medium">{{ core_number_format($payment->total_cost) }}</td>
                    </tr>
                    @if($payment->discount_value > 0)
                    @php
                        $discountAmount = ($payment->discount_type === 'percent')
                            ? $payment->total_cost * $payment->discount_value / 100
                            : $payment->discount_value;
                    @endphp
                    <tr>
                        <td class="text-end text-muted">{{ trans('modules/accounting::accounting.invoice_discount') }}@if($payment->discount_type === 'percent') ({{ $payment->discount_value }}%)@endif</td>
                        <td class="text-end text-danger">-{{ core_number_format($discountAmount) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td class="text-end text-muted">{{ trans('modules/accounting::accounting.invoice_discount') }}</td>
                        <td class="text-end">0</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-end text-muted">{{ trans('modules/accounting::accounting.customer_refundable') }}</td>
                        <td class="text-end fw-bold">{{ core_number_format($payment->value) }}</td>
                    </tr>
                    <tr>
                        <td class="text-end text-muted">{{ trans('modules/accounting::accounting.customer_paid') }}</td>
                        <td class="text-end fw-bold text-success">{{ core_number_format($payment->value_payment) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
            <div>
                @livewire('modules/accounting::payment.payment-actions', ['payment' => $payment], key('actions-' . $payment->id))
            </div>
            <div class="d-flex gap-2">
                @can('accountings.edit')
                    <x-ui::button
                        color="primary"
                        size="sm"
                        icon="pencil"
                        onclick="window.Livewire.dispatch('show-modal-create-sale-invoice', { id: {{ $payment->id }} })"
                    >
                        {{ trans('modules/accounting::accounting.edit') }}
                    </x-ui::button>
                @endcan
                <x-ui::button color="white" size="sm" icon="arrow-back-up" :href="route('products.payment.v2', ['refund_id' => $payment->id])">
                    {{ trans('modules/accounting::accounting.refund') }}
                </x-ui::button>
                <x-ui::button color="white" size="sm" icon="printer" onclick="PoliriumPrint.printUrl('{{ route('products.print.print-payment', $payment->id) }}')">
                    {{ __('In') }}
                </x-ui::button>
            </div>
        </div>
    </div>

    {{-- Tab: Lịch sử thanh toán --}}
    <div x-show="activeTab === 'history'" x-cloak>
        <div class="table-responsive">
            <table class="table table-sm table-vcenter table-striped" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>{{ trans('modules/accounting::accounting.voucher_code') }}</th>
                        <th>{{ trans('modules/accounting::accounting.time') }}</th>
                        <th>{{ trans('modules/accounting::accounting.created_by') }}</th>
                        <th class="text-end">{{ trans('modules/accounting::accounting.voucher_value') }}</th>
                        <th>{{ trans('modules/accounting::accounting.method') }}</th>
                        <th>{{ trans('modules/accounting::accounting.status') }}</th>
                        <th class="text-end">{{ trans('modules/accounting::accounting.receipt_cash') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Generate payment history from type_payment --}}
                    @php
                        $typePayments = is_array($payment->type_payment) ? $payment->type_payment : (is_string($payment->type_payment) ? json_decode($payment->type_payment, true) : []);
                        $paymentMethodCache = $paymentMethodCache ?? \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::pluck('name', 'code')->toArray();
                    @endphp
                    @if($payment->value_payment > 0 && count($typePayments) > 0)
                        @foreach($typePayments as $tpIndex => $tp)
                            @php
                                $historyCode = 'TTHD' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '-' . ($tpIndex + 1);
                                $method = $tp['method'] ?? 'cash';
                                $methodLabel = $tp['label'] ?? ($paymentMethodCache[$method] ?? $method);
                                $tpValue = $tp['value'] ?? 0;
                            @endphp
                            @if($tpValue > 0)
                            <tr>
                                <td><a href="#" class="text-primary">{{ $historyCode }}</a></td>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $payment->user?->name ?? '-' }}</td>
                                <td class="text-end">{{ core_number_format($tpValue) }}</td>
                                <td>{{ $methodLabel }}</td>
                                <td><span class="badge bg-success">{{ trans('modules/accounting::accounting.paid') }}</span></td>
                                <td class="text-end fw-bold">{{ core_number_format($tpValue) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    @elseif($payment->value_payment > 0)
                    {{-- Fallback for old data without structured type_payment --}}
                    <tr>
                        <td><a href="#" class="text-primary">{{ 'TTHD' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</a></td>
                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $payment->user?->name ?? '-' }}</td>
                        <td class="text-end">{{ core_number_format($payment->value_payment) }}</td>
                        <td>{{ trans('modules/accounting::accounting.cash') }}</td>
                        <td><span class="badge bg-success">{{ trans('modules/accounting::accounting.paid') }}</span></td>
                        <td class="text-end fw-bold">{{ core_number_format($payment->value_payment) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ trans('modules/accounting::accounting.no_payment_history') }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Lịch sử trả hàng --}}
    <div x-show="activeTab === 'returns'" x-cloak>
        <div class="table-responsive">
            <table class="table table-sm table-vcenter table-striped" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>{{ trans('modules/accounting::accounting.refund_code') }}</th>
                        <th>{{ trans('modules/accounting::accounting.time') }}</th>
                        <th>{{ trans('modules/accounting::accounting.return_receiver') }}</th>
                        <th class="text-end">{{ trans('modules/accounting::accounting.grand_total') }}</th>
                        <th>{{ trans('modules/accounting::accounting.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $refund)
                    <tr>
                        <td><a href="#" class="text-primary">{{ $refund->code }}</a></td>
                        <td>{{ $refund->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $refund->user->name ?? '-' }}</td>
                        <td class="text-end">{{ core_number_format($refund->value / 100) }}</td>
                        <td><span class="badge bg-success">{{ trans('modules/accounting::accounting.paid_status') }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ trans('modules/accounting::accounting.no_refund_history') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Quick Create Sale Invoice Modal --}}
@livewire('modules/accounting::payment.modal.modal-create-sale-invoice')
