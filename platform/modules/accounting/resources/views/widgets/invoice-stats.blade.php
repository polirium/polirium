@php
    $netBalance = $receipts - $expenses;
    $total = $receipts + $expenses;
    $receiptPercent = $total > 0 ? round($receipts / $total * 100) : 50;
    $expensePercent = $total > 0 ? round($expenses / $total * 100) : 50;
    $balanceColorClass = $netBalance >= 0 ? 'text-success' : 'text-danger';
$strokeColorClass = $netBalance >= 0 ? 'chart-stroke-success' : 'chart-stroke-danger';
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thống kê thu chi</h3>
        <div class="card-actions">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    {{ $period === 'today' ? 'Hôm nay' : ($period === 'week' ? '7 ngày qua' : '30 ngày qua') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item {{ $period === 'today' ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('today')">Hôm nay</a></li>
                    <li><a class="dropdown-item {{ $period === 'week' ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('week')">7 ngày qua</a></li>
                    <li><a class="dropdown-item {{ $period === 'month' ? 'active' : '' }}" href="#" wire:click.prevent="setPeriod('month')">30 ngày qua</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Receipt --}}
            <div class="col-6 col-lg-4">
                <div class="subheader text-success">THU</div>
                <div class="d-flex align-items-baseline">
                    <div class="h2 mb-0 text-success">{{ core_number_format($receipts) }} đ</div>
                    <span class="ms-2 d-inline-flex">
                        {!! tabler_icon('trending-up', ['class' => 'icon icon-sm text-success']) !!}
                    </span>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-success" style="width: {{ $receiptPercent }}%"></div>
                </div>
                <div class="text-muted small mt-1">{{ $receiptCount }} phiếu thu</div>
            </div>

            {{-- Expense --}}
            <div class="col-6 col-lg-4">
                <div class="subheader text-danger">CHI</div>
                <div class="d-flex align-items-baseline">
                    <div class="h2 mb-0 text-danger">{{ core_number_format($expenses) }} đ</div>
                    <span class="ms-2 d-inline-flex">
                        {!! tabler_icon('trending-down', ['class' => 'icon icon-sm text-danger']) !!}
                    </span>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-danger" style="width: {{ $expensePercent }}%"></div>
                </div>
                <div class="text-muted small mt-1">{{ $expenseCount }} phiếu chi</div>
            </div>

            {{-- Balance with Radial --}}
            <div class="col-12 col-lg-4 d-flex align-items-center justify-content-center mt-3 mt-lg-0">
                <div class="d-flex align-items-center">
                    <div class="chart-radial me-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <svg viewBox="0 0 36 36" class="position-absolute" style="transform: rotate(-90deg);">
                            <circle cx="18" cy="18" r="15.915" fill="transparent" class="chart-stroke-gray" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.915" fill="transparent" {{ $strokeColorClass }} stroke-width="3"
                                stroke-dasharray="{{ abs($receiptPercent) }} {{ 100 - abs($receiptPercent) }}" stroke-linecap="round"/>
                        </svg>
                        <div class="text-center">
                            <div class="h5 mb-0 {{ $balanceColorClass }}">{{ $receiptPercent }}%</div>
                        </div>
                    </div>
                    <div>
                        <div class="subheader">CÂN ĐỐI</div>
                        <div class="h3 mb-0 {{ $balanceColorClass }}">
                            {{ $netBalance >= 0 ? '+' : '' }}{{ core_number_format($netBalance) }} đ
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
