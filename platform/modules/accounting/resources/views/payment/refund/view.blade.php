<div>
    <form wire:submit.prevent="save('success')">
        <div class="row">
            <div class="col-md-9">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Thông tin sản phẩm trả hàng</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tìm kiếm sản phẩm (thêm mới nếu không có trong đơn)</label>
                            <input type="text" class="form-control" wire:model.live.debounce.500ms="search" placeholder="Nhập tên hoặc mã sản phẩm...">

                            @if (!empty($lists['products']))
                                <div class="list-group mt-2">
                                    @foreach ($lists['products'] as $item)
                                        <a href="#" class="list-group-item list-group-item-action" wire:click.prevent="selectProduct({{ $item['id'] }})">
                                            {{ $item['name'] }} - {{ $item['code'] }} (Giá vốn: {{ core_number_format($item['cost']) }})
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table-vcenter card-table table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="w-10">Số lượng</th>
                                        <th class="w-15">Đơn giá trả</th>
                                        <th class="w-15">Thành tiền</th>
                                        <th>Ghi chú</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $key => $product)
                                        <tr wire:key="product-{{ $key }}">
                                            <td>
                                                {{ $product['product']['name'] ?? 'Unknown' }}
                                                <div class="text-muted small">{{ $product['product']['code'] ?? '' }}</div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" wire:model.live.debounce.500ms="products.{{ $key }}.amount" min="1">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" wire:model.live.debounce.500ms="products.{{ $key }}.price">
                                            </td>
                                            <td>
                                                {{ core_number_format($product['value']) }}
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" wire:model.live.debounce.500ms="products.{{ $key }}.note">
                                            </td>
                                            <td>
                                                <a href="#" class="text-danger" wire:click.prevent="removeProduct({{ $key }})">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-muted text-center">Chưa có sản phẩm nào</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="fw-bold text-end">Tổng cộng:</td>
                                        <td class="fw-bold">{{ core_number_format($refund->total) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Thông tin phiếu</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Mã phiếu</label>
                            <input type="text" class="form-control" wire:model="refund.code" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Khách hàng</label>
                            <input type="text" class="form-control" value="{{ $this->customer->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" wire:model="refund.note" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex gap-2">
                            @can('accountings.refunds')
                                <button type="submit" class="btn btn-primary w-100">Lưu phiếu trả</button>
                            @endcan
                            <a href="{{ route('accountings.payment.index') }}" class="btn btn-secondary w-100">Hủy</a>
                        </div>
                        @can('accountings.refunds')
                            <div class="mt-2 text-center">
                                <button type="button" wire:click="save('temp')" class="btn btn-link text-muted btn-sm">Lưu nháp</button>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
