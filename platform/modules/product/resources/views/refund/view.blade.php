<div class="p-5 position-relative">
    @if ($isFinished)
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.9); z-index: 10;">
            <div class="text-center bg-white p-5 shadow-lg rounded border border-success">
                <div class="mb-4">
                    {!! tabler_icon('circle-check', ['class' => 'text-success', 'style' => 'width: 80px; height: 80px;']) !!}
                </div>
                <h2 class="text-success mb-3">Hoàn trả hàng thành công!</h2>
                <p class="text-muted mb-4">Mã trả hàng: <strong class="text-dark">{{ $this->refundData['code'] ?? '' }}</strong></p>
                <div class="d-flex gap-2 justify-content-center">
                    <x-ui.button
                        color="success"
                        outline
                        icon="arrow-left"
                        wire:click="$set('isFinished', false)"
                        label="Tiếp tục chỉnh sửa"
                    />
                    <x-ui.button
                        color="danger"
                        icon="x"
                        wire:click="closeTab"
                        label="Đóng Tab này"
                    />
                </div>
            </div>
        </div>
    @endif

    {{-- Flash messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <x-ui::card>
                <x-ui::table striped class="table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>Mã SP</th>
                            <th>Tên sản phẩm</th>
                            <th>Đã bán</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Giảm giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $key => $item)
                            <tr>
                                <td>
                                    <x-ui.button
                                        color="danger"
                                        icon="trash"
                                        wire:click="removeProduct({{ $key }})"
                                        :disabled="$isFinished"
                                    />
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['product']['code'] ?? '' }}</td>
                                <td>{{ $item['product']['name'] ?? '' }}</td>
                                <td>{{ $item['sold_amount'] ?? 0 }}</td>
                                <td>
                                    <x-form::input
                                        type="number"
                                        wire:model.live="products.{{ $key }}.amount"
                                        min="1"
                                        max="{{ $item['sold_amount'] ?? 1 }}"
                                        :disabled="$isFinished"
                                    />
                                </td>
                                <td>
                                    <x-form::currency
                                        wire:model.live="products.{{ $key }}.price"
                                        :disabled="$isFinished"
                                    />
                                </td>
                                <td style="width: 200px;">
                                    <x-ui::dropdown>
                                        <x-slot name="label">
                                            <div class="text-end">
                                                {{ core_number_format((int)($item['discount_value'] ?? 0)) }}
                                                {{ ($item['discount_type'] ?? 'number') === 'percent' ? '%' : 'VNĐ' }}
                                                @if ((int)($item['discount_value'] ?? 0) > 0)
                                                    <br><small class="text-danger">-{{ core_number_format((int)($item['discount_value'] ?? 0)) }} {{ ($item['discount_type'] ?? 'number') === 'percent' ? '%' : 'VNĐ' }}</small>
                                                @endif
                                            </div>
                                        </x-slot>

                                        <div class="px-4 py-3" style="min-width: 280px;">
                                            <div class="mb-3">
                                                <x-form::currency
                                                    wire:model.live.debounce.500ms="products.{{ $key }}.price"
                                                    label="Đơn giá"
                                                    :disabled="$isFinished"
                                                />
                                            </div>
                                            <div class="mb-3">
                                                <x-form::currency
                                                    wire:model.live.debounce.500ms="products.{{ $key }}.discount_value"
                                                    label="Giảm giá"
                                                    :disabled="$isFinished"
                                                >
                                                    <x-slot name="append">
                                                        <x-ui.button type="button"
                                                            wire:click="$set('products.{{ $key }}.discount_type', 'percent')"
                                                            icon="percentage"
                                                            :color="(($item['discount_type'] ?? 'number') == 'percent') ? 'primary' : false"
                                                            :disabled="$isFinished"
                                                        />
                                                        <x-ui.button type="button"
                                                            wire:click="$set('products.{{ $key }}.discount_type', 'number')"
                                                            icon="currency-dong"
                                                            :color="(($item['discount_type'] ?? 'number') == 'number') ? 'primary' : false"
                                                            :disabled="$isFinished"
                                                        />
                                                    </x-slot>
                                                </x-form::currency>
                                            </div>
                                            <div class="mb-3">
                                                <x-form::currency
                                                    wire:model.live.debounce.500ms="products.{{ $key }}.value"
                                                    label="Thành tiền"
                                                    :disabled="$isFinished"
                                                />
                                            </div>
                                        </div>
                                    </x-ui::dropdown>
                                </td>
                                <td>
                                    {{ core_number_format($item['value'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Chưa có sản phẩm</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui::table>
            </x-ui::card>
        </div>

        <div class="col-md-4">
            <x-ui::card>
                <x-ui::table class="table-bordered">
                    <tr>
                        <td class="w-50"><b>Mã trả hàng:</b></td>
                        <td class="text-end">
                            <x-form::input wire:model="refundData.code" :disabled="$isFinished" />
                        </td>
                    </tr>

                    <tr>
                        <td><b>Tổng tiền gốc:</b> <x-ui::badge color="primary" :label="count((array)$products)" /></td>
                        <td class="text-end">
                            {{ core_number_format(($refundData['original_total_amount'] ?? 0) / 100) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>Tổng sau giảm SP:</b></td>
                        <td class="text-end">
                            {{ core_number_format($total_after_product_discount) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>Giảm giá:</b></td>
                        <td class="text-end">
                            <x-ui::dropdown>
                                <x-slot name="label">
                                    {{ core_number_format((int)($refundData['discount_value'] ?? 0)) }} {{ ($refundData['discount_type'] ?? 'number') === 'percent' ? '%' : 'VNĐ' }}
                                </x-slot>

                                <div class="px-4 py-3" style="min-width: 250px;">
                                    <x-form::currency
                                        wire:model.live="refundData.discount_value"
                                        placeholder="Giảm giá"
                                        :disabled="$isFinished"
                                    >
                                        <x-slot name="append">
                                            <x-ui.button type="button"
                                                wire:click="$set('refundData.discount_type', 'percent')"
                                                icon="percentage"
                                                :color="($refundData['discount_type'] ?? 'number') == 'percent' ? 'primary' : false"
                                                :disabled="$isFinished"
                                            />
                                            <x-ui.button type="button"
                                                wire:click="$set('refundData.discount_type', 'number')"
                                                icon="currency-dong"
                                                :color="($refundData['discount_type'] ?? 'number') == 'number' ? 'primary' : false"
                                                :disabled="$isFinished"
                                            />
                                        </x-slot>
                                    </x-form::currency>
                                </div>
                            </x-ui::dropdown>
                        </td>
                    </tr>

                    <tr>
                        <td><b>Tổng phải trả:</b></td>
                        <td class="text-end">
                            {{ core_number_format($total_after_refund_discount) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>Phí trả hàng:</b></td>
                        <td class="text-end">
                            <x-ui::dropdown>
                                <x-slot name="label">
                                    {{ core_number_format((int)($refundData['refund_fee'] ?? 0)) }} {{ ($refundData['refund_fee_type'] ?? 'number') === 'percent' ? '%' : 'VNĐ' }}
                                </x-slot>

                                <div class="px-4 py-3" style="min-width: 250px;">
                                    <x-form::currency
                                        wire:model.live="refundData.refund_fee"
                                        placeholder="Phí trả hàng"
                                        :disabled="$isFinished"
                                    >
                                        <x-slot name="append">
                                            <x-ui.button type="button"
                                                wire:click="$set('refundData.refund_fee_type', 'percent')"
                                                icon="percentage"
                                                :color="($refundData['refund_fee_type'] ?? 'number') == 'percent' ? 'primary' : false"
                                                :disabled="$isFinished"
                                            />
                                            <x-ui.button type="button"
                                                wire:click="$set('refundData.refund_fee_type', 'number')"
                                                icon="currency-dong"
                                                :color="($refundData['refund_fee_type'] ?? 'number') == 'number' ? 'primary' : false"
                                                :disabled="$isFinished"
                                            />
                                        </x-slot>
                                    </x-form::currency>
                                </div>
                            </x-ui::dropdown>
                        </td>
                    </tr>

                    <tr>
                        <td><b>Tổng tiền trả:</b></td>
                        <td class="text-end">
                            <b class="text-success fs-4">{{ core_number_format(($refundData['value'] ?? 0) / 100) }}</b>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <x-form::input wire:model.live="refundData.note" label="Ghi chú" :disabled="$isFinished" />
                            <x-ui::errors />
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <x-ui.button
                                color="success"
                                icon="device-floppy"
                                wire:click="submitRefund"
                                wire:loading.attr="disabled"
                                wire:target="submitRefund"
                                label="Lưu trả hàng"
                                style="width: 100%;"
                                :disabled="empty($refundData['product_payment_id']) || empty($products) || $isFinished"
                            />
                            <div wire:loading wire:target="submitRefund" class="text-center mt-2">
                                <span class="spinner-border spinner-border-sm"></span> Đang xử lý...
                            </div>
                        </td>
                    </tr>
                </x-ui::table>
            </x-ui::card>
        </div>
    </div>
</div>
