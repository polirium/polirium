@extends('core/ui::base.base')

@section('content')
    @livewire('modules/product::refund.view', ['payment_id' => $id])
@endsection
