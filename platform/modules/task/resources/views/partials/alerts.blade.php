@if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        <div class="d-flex align-items-center gap-2">
            {!! tabler_icon('circle-check', ['class' => 'icon alert-icon']) !!}
            <div>{{ session('success') }}</div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
        <div class="d-flex align-items-center gap-2">
            {!! tabler_icon('alert-circle', ['class' => 'icon alert-icon']) !!}
            <div>{{ session('error') }}</div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="d-flex gap-2">
            {!! tabler_icon('alert-triangle', ['class' => 'icon alert-icon']) !!}
            <div>
                <div class="fw-bold">{{ __('modules/task::task.form_has_errors') }}</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
