<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::project.create') }}</x-slot:title>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            @include('modules/task::partials.alerts')
            <form method="POST" action="{{ route('admin.projects.store') }}" novalidate>
                @csrf
                @include('modules/task::project._form')
            </form>
        </div>
    </div>
</x-ui.layouts::app>
