<x-ui.layouts::app>
    <x-slot:title>{{ __('Báo cáo xuất nhập tồn') }}</x-slot:title>

    <div class="row">
        <div class="col-md-3">
            <x-ui::card>
                <form method="GET" class="p-3">
                    <div class="mb-3"><label class="form-label">{{ __('Báo cáo') }}</label><select name="mode" class="form-select"><option value="movement" @selected($mode === 'movement')>{{ __('Xuất nhập tồn') }}</option><option value="value" @selected($mode === 'value')>{{ __('Giá trị kho') }}</option></select></div>
                    <div class="mb-3"><label class="form-label">{{ __('Chọn nhanh') }}</label><select name="period" class="form-select" onchange="inventoryReportPeriod(this.value)"><option value="custom" @selected(!in_array(request('period'), ['today', 'yesterday', 'last_7_days', 'this_month', 'last_month']))>{{ __('Tùy chọn khoảng ngày') }}</option><option value="today" @selected(request('period') === 'today')>{{ __('Hôm nay') }}</option><option value="yesterday" @selected(request('period') === 'yesterday')>{{ __('Hôm qua') }}</option><option value="last_7_days" @selected(request('period') === 'last_7_days')>{{ __('7 ngày gần nhất') }}</option><option value="this_month" @selected(request('period') === 'this_month')>{{ __('Tháng này') }}</option><option value="last_month" @selected(request('period') === 'last_month')>{{ __('Tháng trước') }}</option></select></div>
                    @if($mode === 'movement')<div class="mb-3"><label class="form-label">{{ __('Từ ngày') }}</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">{{ __('Đến ngày') }}</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
                    @endif
                    <div class="mb-3"><label class="form-label">{{ __('Hàng hóa') }}</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Mã hoặc tên hàng') }}"></div>
                    <button class="btn btn-primary w-100">{{ __('Xem báo cáo') }}</button>
                </form>
            </x-ui::card>
        </div>
        <div class="col-md-9">
            <x-ui::card>
                <div class="card-header"><h3 class="card-title">{{ $mode === 'value' ? __('Báo cáo giá trị kho') : __('Báo cáo xuất nhập tồn') }} @if($mode === 'movement')<span class="text-muted fw-normal">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span>@endif</h3></div>
                <div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>{{ __('Mã hàng') }}</th><th>{{ __('Tên hàng') }}</th>@if($mode === 'value')<th class="text-end">{{ __('Tồn kho') }}</th><th class="text-end">{{ __('Giá bán') }}</th>@can('products.view-cost')<th class="text-end">{{ __('Giá vốn') }}</th><th class="text-end">{{ __('Giá trị kho') }}</th>@endcan @else <th class="text-end">{{ __('Tồn đầu') }}</th><th class="text-end">{{ __('Nhập') }}</th><th class="text-end">{{ __('Xuất') }}</th><th class="text-end">{{ __('Tồn cuối') }}</th>@can('products.view-cost')<th class="text-end">{{ __('Giá trị cuối') }}</th>@endcan @endif</tr></thead><tbody>
                @if($mode === 'value') @foreach($valueRows as $item)<tr><td>{{ $item->code }}</td><td>{{ $item->name }} <span class="text-muted">{{ $item->unit }}</span></td><td class="text-end">{{ core_number_format($item->qty) }}</td><td class="text-end">{{ core_number_format($item->price) }}</td>@can('products.view-cost')<td class="text-end">{{ core_number_format($item->cost) }}</td><td class="text-end fw-bold">{{ core_number_format($item->qty * $item->cost) }}</td>@endcan</tr>@endforeach @else @forelse($logs as $item) @php
                    $hasMovement = $item->opening_qty || $item->inbound_qty || $item->outbound_qty;
                    $closing = $hasMovement ? $item->opening_qty + $item->inbound_qty - $item->outbound_qty : $item->current_qty;
                @endphp <tr><td>{{ $item->code }}</td><td>{{ $item->name }} <span class="text-muted">{{ $item->unit }}</span></td><td class="text-end">{{ core_number_format($hasMovement ? $item->opening_qty : $item->current_qty) }}</td><td class="text-end text-success">{{ core_number_format($item->inbound_qty) }}</td><td class="text-end text-danger">{{ core_number_format($item->outbound_qty) }}</td><td class="text-end fw-bold">{{ core_number_format($closing) }}</td>@can('products.view-cost')<td class="text-end">{{ core_number_format($closing * $item->cost) }}</td>@endcan</tr> @empty <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Không có dữ liệu trong kỳ') }}</td></tr> @endforelse @endif
                </tbody></table></div>
            </x-ui::card>
        </div>
    </div>
</x-ui.layouts::app>

@push('scripts')
<script>
function inventoryReportPeriod(period) {
    const from = document.querySelector('[name="from"]');
    const to = document.querySelector('[name="to"]');
    if (!from || !to || period === 'custom') return;
    const today = new Date();
    const format = date => date.toISOString().slice(0, 10);
    let start = new Date(today), end = new Date(today);
    if (period === 'yesterday') start = end = new Date(today.setDate(today.getDate() - 1));
    if (period === 'last_7_days') start.setDate(today.getDate() - 6);
    if (period === 'this_month') start = new Date(today.getFullYear(), today.getMonth(), 1);
    if (period === 'last_month') { start = new Date(today.getFullYear(), today.getMonth() - 1, 1); end = new Date(today.getFullYear(), today.getMonth(), 0); }
    from.value = format(start); to.value = format(end);
}
</script>
@endpush
