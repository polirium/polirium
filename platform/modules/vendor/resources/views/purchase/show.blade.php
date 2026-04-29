@extends('core/ui::base.base')

@section('title', __('Chi tiết phiếu nhập #:code', ['code' => $purchase->code]))

@section('content_header')
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">{{ __('Chi tiết phiếu nhập #:code', ['code' => $purchase->code]) }}</h2>
            <div class="text-muted mt-1">
                {{ $purchase->created_at->format('d/m/Y H:i') }} | {{ $purchase->userCreated->name ?? '-' }}
            </div>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                @can('vendors.purchases.edit')
                    <a href="{{ route('vendors.purchases.order', $purchase->id) }}" class="btn btn-warning">
                        {!! tabler_icon('pencil', ['class' => 'icon']) !!} {{ trans('modules/vendor::purchase.edit') ?? 'Sửa' }}
                    </a>
                @endcan
                
                @can('vendors.refunds.index')
                    <a href="{{ route('vendors.purchases.refund', ['id' => $purchase->refund_id ?? 0, 'purchase_id' => $purchase->id]) }}" class="btn btn-danger">
                        {!! tabler_icon('arrow-back-up', ['class' => 'icon']) !!} {{ __('Trả hàng nhập') }}
                    </a>
                @endcan

                @can('vendors.purchases.create')
                    <a href="{{ route('vendors.purchases.order', ['copy_id' => $purchase->id]) }}" class="btn btn-secondary">
                        {!! tabler_icon('copy', ['class' => 'icon']) !!} {{ __('Sao chép') }}
                    </a>
                @endcan

                @can('vendors.purchases.index')
                    <a href="{{ route('vendors.purchases.export', $purchase->id) }}" class="btn btn-primary" target="_blank">
                        {!! tabler_icon('file-export', ['class' => 'icon']) !!} {{ __('Xuất file') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $row = $purchase; // Để reuse biến $row cho file detail
        $id = $row->id;
        $hideToolbar = true;
    @endphp
    @include('modules/vendor::purchase.index.datatable.detail')
@endsection
