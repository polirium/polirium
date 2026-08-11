@php
    $periodLabels = [
        'today' => 'Hôm nay',
        'week' => 'Tuần này',
        'month' => 'Tháng này',
        'year' => 'Năm nay',
    ];
@endphp

<div class="card h-100">
    <div class="card-header align-items-center">
        <div>
            <h3 class="card-title">Khách hàng mua nhiều</h3>
            <div class="text-muted small mt-1">{{ core_number_format($totalCustomers) }} khách có đơn hoàn tất</div>
        </div>
        <div class="card-actions">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    {{ $periodLabels[$period] ?? $periodLabels['today'] }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach($periodLabels as $value => $label)
                        <li>
                            <a class="dropdown-item {{ $period === $value ? 'active' : '' }}"
                               href="#"
                               wire:click.prevent="setPeriod('{{ $value }}')">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @if($customers->isEmpty())
        <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
            <span class="avatar avatar-lg bg-blue-lt mb-3">
                {!! tabler_icon('users-group', ['class' => 'icon text-primary']) !!}
            </span>
            <div class="fw-medium">Chưa có khách hàng mua hàng</div>
            <div class="text-muted small mt-1">Thử chọn một khoảng thời gian khác.</div>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach($customers as $customer)
                @php
                    $rank = $loop->iteration;
                    $progress = min(100, round(((int) $customer->total_quantity / $maxQuantity) * 100));
                @endphp
                <div class="list-group-item py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm {{ $rank === 1 ? 'bg-yellow-lt text-yellow' : 'bg-blue-lt text-primary' }} fw-bold flex-shrink-0">
                            {{ $rank }}
                        </span>
                        <div class="flex-fill overflow-hidden">
                            <div class="d-flex align-items-baseline justify-content-between gap-3">
                                <div class="text-truncate">
                                    <span class="fw-semibold">{{ $customer->name ?: 'Khách chưa đặt tên' }}</span>
                                    <span class="text-muted small ms-1">{{ $customer->masked_phone }}</span>
                                </div>
                                <div class="text-nowrap">
                                    <span class="fw-bold text-primary">{{ core_number_format($customer->total_quantity) }}</span>
                                    <span class="text-muted small"> sản phẩm</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <div class="progress progress-xs flex-fill">
                                    <div class="progress-bar {{ $rank === 1 ? 'bg-yellow' : 'bg-primary' }}" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-muted small text-nowrap">{{ core_number_format($customer->total_orders) }} đơn</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
