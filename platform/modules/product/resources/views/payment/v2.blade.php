@extends('core/ui::base.base')

@section('content')
    {{-- Directly render the V2 component --}}
    @livewire('modules/product::payment.tab-payment-v2-component', ['tab_selected' => 'v2'])
@endsection
