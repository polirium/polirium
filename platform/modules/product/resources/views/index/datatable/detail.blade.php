@php
    // PowerGrid passes $row as stdClass, we need the actual Product model for accessors
    $product = \Polirium\Modules\Product\Http\Model\Product::with(['category', 'trademark', 'shelve', 'branches', 'logs', 'elements.element'])->find($id);

    $totalStock = $product->type === 'service' ? null : ($product->branches?->sum('pivot.qty') ?? 0);
    $isUnlimitedStock = $product->amount >= 1e15; // Kiểm tra tồn kho không giới hạn
    $typeColor = match($product->type) {
        'product' => 'primary',
        'service' => 'success',
        'combo' => 'info',
        default => 'secondary'
    };
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
        localTab: 1,
        initWidth() {
            const container = this.$el.closest('.table-responsive');
            if(container) {
                this.$el.style.width = container.clientWidth + 'px';
            }
        }
    }"
    x-init="initWidth(); window.addEventListener('resize', () => initWidth())">
        {{-- Product Header --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-{{ $typeColor }}-lt text-{{ $typeColor }}">
                        {{ $product->type_name }}
                    </span>
                    <span class="badge bg-muted-lt">
                        {{ $product->code }}
                    </span>
                </div>
                <h3 class="mb-1"><strong>{{ $product->name_unit }}</strong></h3>
                <p class="text-muted small mb-0">
                    {{ $product->category_parent }}
                    @if($product->trademark)
                        <span class="mx-1">•</span>
                        {{ $product->trademark->name }}
                    @endif
                </p>
            </div>
            <div class="text-end">
                <div class="h2 mb-0 text-primary">{{ core_number_format($product->price) }} đ</div>
                <div class="text-muted small">Giá bán</div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div x-data="{ localTab: 1 }">
            <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link cursor-pointer" :class="{ 'active': localTab == 1 }" @click.prevent="localTab = 1">
                        {!! tabler_icon('info-circle', ['class' => 'icon me-1']) !!}
                        {{ trans('modules/product::product.information') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link cursor-pointer" :class="{ 'active': localTab == 2 }" @click.prevent="localTab = 2">
                        {!! tabler_icon('package', ['class' => 'icon me-1']) !!}
                        {{ trans('modules/product::product.stock') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link cursor-pointer" :class="{ 'active': localTab == 3 }" @click.prevent="localTab = 3">
                        {!! tabler_icon('history', ['class' => 'icon me-1']) !!}
                        {{ trans('modules/product::product.stock_card') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link cursor-pointer" :class="{ 'active': localTab == 4 }" @click.prevent="localTab = 4">
                        {!! tabler_icon('package', ['class' => 'icon me-1']) !!}
                        {{ trans('modules/product::product.component_products') }}
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content">
                {{-- Tab 1: Thông tin --}}
                <div class="tab-pane" :class="{ 'show active': localTab == 1 }" x-show="localTab == 1">
                    <div class="row g-4">
                        {{-- Left: Product Info --}}
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="card card-sm">
                                        <div class="card-body py-2">
                                            <div class="text-muted small mb-1">{{ trans('modules/product::product.product_type') }}</div>
                                            <div><strong>{{ $product->type_name }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card card-sm">
                                        <div class="card-body py-2">
                                            <div class="text-muted small mb-1">{{ trans('modules/product::product.brand') }}</div>
                                            <div><strong>{{ $product->trademark?->name ?? '-' }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card card-sm">
                                        <div class="card-body py-2">
                                            <div class="text-muted small mb-1">Định {{ trans('modules/product::product.stock_level') }}</div>
                                            <div><strong>{{ $product->min_quantity }} - {{ $product->max_quantity }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                                @can('products.view-cost')
                                <div class="col-sm-6">
                                    <div class="card card-sm">
                                        <div class="card-body py-2">
                                            <div class="text-muted small mb-1">{{ trans('modules/product::product.cost_price') }}</div>
                                            <div><strong>{{ core_number_format($product->cost) }} đ</strong></div>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                                <div class="col-sm-6">
                                    <div class="card card-sm">
                                        <div class="card-body py-2">
                                            <div class="text-muted small mb-1">{{ trans('modules/product::product.weight') }}</div>
                                            <div><strong>{{ $product->weight }} {{ $product->weight_type }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card card-sm">
                                        <div class="card-body py-2">
                                            <div class="text-muted small mb-1">{{ trans('modules/product::product.location') }}</div>
                                            <div><strong>{{ $product->shelve?->name ?? '-' }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right: Description & Notes --}}
                        <div class="col-md-4">
                            <div class="card card-sm h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        {!! tabler_icon('file-text', ['class' => 'icon text-muted me-1']) !!}
                                        {{ trans('modules/product::product.description_notes') }}
                                    </h6>
                                    <div class="small">
                                        <div class="mb-3">
                                            <span class="text-muted">{{ trans('modules/product::product.description') }}:</span>
                                            <p class="mb-0 mt-1">{{ $product->description ?: '-' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-muted">{{ trans('modules/product::product.note') }}:</span>
                                            <p class="mb-0 mt-1">{{ $product->note ?: '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Tồn kho --}}
                <div class="tab-pane" :class="{ 'show active': localTab == 2 }" x-show="localTab == 2" x-cloak>
                    @if ($product->type !== 'service')
                        @if($totalStock > 0 || $isUnlimitedStock)
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="card card-sm bg-primary-lt">
                                        <div class="card-body text-center py-3">
                                            @if($isUnlimitedStock)
                                                <div class="h1 mb-0 text-muted" title="Không giới hạn">∞</div>
                                            @else
                                                <div class="h1 mb-0">{{ $totalStock }}</div>
                                            @endif
                                            <div class="text-muted small">Tổng tồn kho</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <x-ui::table striped class="card-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('Chi nhánh') }}</th>
                                    <th class="text-end">{{ trans('modules/product::product.stock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($product->branches && $product->branches->count() > 0)
                                    @foreach ($product->branches as $item)
                                        <tr>
                                            <td><strong>{{ $item->name }}</strong></td>
                                            <td class="text-end">
                                                <span class="badge {{ $item->pivot->qty > 0 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                                    {{ $item->pivot->qty }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </x-ui::table>
                    @endif
                </div>

                {{-- Tab 3: Thẻ kho --}}
                <div class="tab-pane" :class="{ 'show active': localTab == 3 }" x-show="localTab == 3" x-cloak>
                    <x-ui::table striped class="card-table">
                        <thead>
                            <tr>
                                <th>{{ trans('Chứng từ') }}</th>
                                <th>{{ trans('modules/product::product.time') }}</th>
                                <th>{{ trans('modules/product::product.transaction_type') }}</th>
                                <th>{{ __('Trạng thái') }}</th>
                                <th>{{ trans('modules/product::product.partner') }}</th>
                                @can('products.view-cost')
                                <th class="text-end">{{ trans('modules/product::product.transaction_price') }}</th>
                                <th class="text-end">{{ trans('modules/product::product.cost_price') }}</th>
                                @endcan
                                <th class="text-end">{{ trans('modules/product::product.quantity') }}</th>
                                <th class="text-end">{{ trans('modules/product::product.ending_stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($product->logs && $product->logs->count() > 0)
                                @foreach ($product->logs as $item)
                                    <tr>
                                        <td>
                                            @php
                                                $isRefund = str_starts_with($item->display_code, 'TR.');
                                                $badgeClass = $isRefund ? 'bg-danger-lt text-danger fw-bold' : 'bg-muted-lt text-decoration-none';
                                            @endphp
                                            @if($item->url)
                                                <a href="{{ $item->url }}" class="badge {{ $badgeClass }}" target="_blank" title="Xem chi tiết">
                                                    {{ $item->display_code }}
                                                </a>
                                            @else
                                                <span class="badge {{ $badgeClass }}">{{ $item->display_code }}</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ core_format_date($item->created_at) }}</td>
                                        <td>{{ $item->type_name }}</td>
                                        <td>
                                            @if($item->order_status)
                                                <span class="badge {{ $item->order_status['class'] }}">{{ $item->order_status['label'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->partner_name ?? '-' }}</td>
                                        @can('products.view-cost')
                                        <td class="text-end">{{ core_number_format($item->value_before) }}</td>
                                        <td class="text-end">{{ core_number_format($item->value_after) }}</td>
                                        @endcan
                                        <td class="text-end">
                                            <span class="badge {{ $item->signed_amount >= 0 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                                {{ $item->signed_amount > 0 ? '+' : '' }}{{ core_number_format($item->signed_amount) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ core_number_format($item->amount_after ?? 0) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </x-ui::table>
                </div>

                {{-- Tab 4: Hàng hoá thành phần --}}
                <div class="tab-pane" :class="{ 'show active': localTab == 4 }" x-show="localTab == 4" x-cloak>
                    <x-ui::table striped class="card-table">
                        <thead>
                            <tr>
                                <th class="w-1">{{ trans('modules/product::product.order_no') }}</th>
                                <th>{{ trans('modules/product::product.product_code') }}</th>
                                <th>{{ trans('modules/product::product.product_name') }}</th>
                                <th class="text-end">{{ trans('modules/product::product.quantity') }}</th>
                                <th class="text-end">{{ trans('modules/product::product.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($product->elements && $product->elements->count() > 0)
                                @foreach ($product->elements as $element)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><span class="badge bg-muted-lt">{{ $element->element->code }}</span></td>
                                        <td><strong>{{ $element->element->name }}</strong></td>
                                        <td class="text-end">{{ $element->qty }}</td>
                                        <td class="text-end">{{ core_number_format($element->price) }} đ</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </x-ui::table>
                </div>
            </div>
        </div>

        <hr>

        {{-- Action Buttons --}}
        <div class="d-flex justify-content-end gap-2">
            @can('products.edit')
            {{-- Edit button --}}
            <button
                class="btn btn-primary btn-sm"
                data-bs-toggle="tooltip"
                title="Sửa"
                wire:click="$dispatch('show-modal-create-product', { id: {{ $id }}, type: '{{ $row->type }}' })"
            >
                {!! tabler_icon('pencil', ['class' => 'icon']) !!}
                Sửa
            </button>
            @endcan

            @if(auth()->user()?->canAny(['products.create', 'products.destroy']))
            {{-- Dropdown Menu --}}
            <div class="dropdown">
                <button class="btn btn-white btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {!! tabler_icon('dots', ['class' => 'icon']) !!}
                    Thêm
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    @can('products.create')
                    {{-- Copy action --}}
                    <button class="dropdown-item" wire:click="$dispatch('triggerCopyProduct', { id: {{ $id }} })">
                        {!! tabler_icon('copy', ['class' => 'icon dropdown-item-icon']) !!}
                        Copy sản phẩm
                    </button>
                    @endcan

                    @can('products.destroy')
                    <div class="dropdown-divider"></div>

                    {{-- Delete action --}}
                    <button
                        class="dropdown-item text-danger"
                        wire:click="$dispatch('triggerRemoveProduct', { id: {{ $id }} })"
                        wire:confirm="{{ trans('Are you sure you want to delete this product?') }}"
                    >
                        {!! tabler_icon('trash', ['class' => 'icon dropdown-item-icon']) !!}
                        Xóa sản phẩm
                    </button>
                    @endcan
                </div>
            </div>
            @endif
        </div>
</div>
