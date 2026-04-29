<div>
    <div class="row g-0 h-100" style="min-height: calc(100vh - 140px);">
        <!-- Sidebar: List Forms -->
        <div class="col-12 col-md-4 col-lg-3 border-end d-flex flex-column z-1 bg-white shadow-sm">
            <!-- Sidebar Header -->
            <div class="border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title h3 fw-bold text-dark mb-0">{{ trans('modules/print-forms::editor.form_list') }}</h3>
                    @can('print-forms.forms.create')
                        <button class="btn btn-primary btn-icon btn-sm shadow-sm"
                                wire:click="$dispatch('show-modal-create-form', { id: 0, type: '{{ $tab }}' })"
                                data-bs-toggle="tooltip"
                                title="{{ trans('modules/print-forms::editor.create_new_form') }}">
                            {{ tabler_icon('plus') }}
                        </button>
                    @endcan
                </div>

                <!-- Search -->
                <div class="input-icon mb-3">
                    <span class="input-icon-addon">
                        {{ tabler_icon('search', ['class' => 'text-muted']) }}
                    </span>
                    <input type="text"
                           class="form-control bg-light border-0"
                           placeholder="{{ trans('core/base::general.search_placeholder') }}..."
                           wire:model.live.debounce.300ms="search">
                </div>

                <!-- Tabs for Form Types -->
                <div class="nav nav-pills nav-fill bg-light rounded-2 p-1" role="tablist">
                    @foreach (['invoice', 'receipt', 'payment'] as $type)
                        <a href="#"
                           class="nav-link small fw-medium {{ $tab == $type ? 'active bg-white shadow-sm text-primary' : 'text-muted' }} px-2 py-1"
                           style="transition: all 0.2s ease;"
                           wire:click.prevent="$set('tab', '{{ $type }}')">
                            {{ trans('modules/print-forms::editor.' . $type) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- List Content -->
            <div class="list-group list-group-flush flex-fill custom-scrollbar overflow-auto">
                @forelse($forms as $item)
                    <div class="list-group-item list-group-item-action border-bottom border-start-0 border-end-0 {{ $form_id == $item['id'] ? 'bg-primary-subtle' : 'hover-bg-light' }} p-3"
                         style="cursor: pointer; transition: background-color 0.2s ease; border-left: 3px solid {{ $form_id == $item['id'] ? 'var(--tblr-primary)' : 'transparent' }} !important;"
                         wire:click="selectForm({{ $item['id'] }})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-truncate me-2">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fw-bold text-dark text-truncate {{ $form_id == $item['id'] ? 'text-primary' : '' }}" title="{{ $item['name'] }}">
                                        {{ $item['name'] }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-sm {{ $item['active'] ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                        {{ $item['active'] ? trans('modules/print-forms::editor.active') : trans('modules/print-forms::editor.hidden') }}
                                    </span>
                                    <span class="text-muted small">#{{ $item['id'] }}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center hover-opacity-100 {{ $form_id == $item['id'] ? 'opacity-100' : '' }} gap-1 opacity-0 transition-opacity" wire:click.stop>
                                @can('print-forms.forms.edit')
                                @if (!$item['active'])
                                    <button class="btn btn-icon btn-sm btn-ghost-success rounded-circle"
                                            wire:click="toggleActive({{ $item['id'] }})"
                                            data-bs-toggle="tooltip"
                                            title="{{ trans('modules/print-forms::editor.set_as_main') }}">
                                        {{ tabler_icon('check', ['class' => 'icon-sm']) }}
                                    </button>
                                @endif
                                @endcan
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-sm btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown">
                                        {{ tabler_icon('dots-vertical', ['class' => 'icon-sm']) }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                        @can('print-forms.forms.edit')
                                        <a class="dropdown-item" href="#" wire:click.prevent="$dispatch('show-modal-create-form', { id: {{ $item['id'] }}, type: '{{ $tab }}' })">
                                            {{ tabler_icon('pencil', ['class' => 'icon me-2 text-muted']) }}
                                            {{ trans('modules/print-forms::editor.edit') }}
                                        </a>
                                        @endcan
                                        @can('print-forms.forms.delete')
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#"
                                           wire:click.prevent="deleteForm({{ $item['id'] }})"
                                           wire:confirm="{{ trans('modules/print-forms::editor.delete_confirm') }}">
                                            {{ tabler_icon('trash', ['class' => 'icon me-2']) }}
                                            {{ trans('modules/print-forms::editor.delete_confirm') }}
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted p-4 text-center">
                        <div class="bg-light rounded-circle mb-3 p-3">
                            {{ tabler_icon('file-off', ['class' => 'icon-lg text-secondary']) }}
                        </div>
                        <p class="h4 text-dark mb-1">{{ trans('modules/print-forms::editor.no_forms_yet') }}</p>
                        <p class="small mb-3">{{ trans('modules/print-forms::editor.no_forms_subtitle') }}</p>
                        @can('print-forms.forms.create')
                        <button class="btn btn-outline-primary btn-sm"
                                wire:click="$dispatch('show-modal-create-form', { id: 0, type: '{{ $tab }}' })">
                            {{ tabler_icon('plus', ['class' => 'icon-sm me-1']) }}
                            {{ trans('modules/print-forms::editor.create_new_form') }}
                        </button>
                        @endcan
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Main Content: Preview & Editor -->
        <div class="col-12 col-md-8 col-lg-9 bg-light d-flex flex-column position-relative">
            @if ($this->form)
                <!-- Toolbar -->
                <div class="border-bottom d-flex justify-content-between align-items-center z-1 sticky-top bg-white px-4 py-2 shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-2" style="width: 40px; height: 40px;">
                            {{ tabler_icon('file-text', ['class' => 'icon-md']) }}
                        </div>
                        <div>
                            <h2 class="h3 fw-bold text-dark lh-1 mb-0">{{ $this->form->name }}</h2>
                            <div class="d-flex align-items-center mt-1 gap-2">
                                <span class="badge badge-outline text-muted fw-normal text-uppercase border" style="font-size: 10px; letter-spacing: 0.5px;">
                                    {{ trans('modules/print-forms::editor.' . $this->form->type) }}
                                </span>
                                @if ($this->form->active)
                                    <span class="d-flex align-items-center text-success small fw-medium">
                                        <span class="status-dot status-dot-animated bg-success me-1"></span>
                                        {{ trans('modules/print-forms::editor.active') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center me-2">
                            <span class="text-muted small me-2 text-nowrap">{{ trans('modules/print-forms::editor.paper_size') }}:</span>
                            <select class="form-select form-select-sm" wire:model.live="paperSize" style="width: 140px;">
                                <option value="a4">{{ trans('modules/print-forms::editor.size_a4') }}</option>
                                <option value="a5">{{ trans('modules/print-forms::editor.size_a5') }}</option>
                                <option value="k80">{{ trans('modules/print-forms::editor.size_k80') }}</option>
                                <option value="k57">{{ trans('modules/print-forms::editor.size_k57') }}</option>
                            </select>
                        </div>

                        <div class="btn-list">
                            @can('print-forms.forms.edit')
                            <button class="btn btn-outline-secondary"
                                    wire:click="$dispatch('show-modal-create-form', { id: {{ $this->form->id }}, type: '{{ $tab }}' })">
                                {{ tabler_icon('pencil', ['class' => 'icon me-1']) }}
                                {{ trans('modules/print-forms::editor.edit') }}
                            </button>
                            @endcan
                            <button class="btn btn-primary"
                                    onclick="printPreview()">
                                {{ tabler_icon('printer', ['class' => 'icon me-1']) }}
                                {{ trans('modules/print-forms::editor.print_preview') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Canvas -->
                <div class="flex-fill d-flex justify-content-center bg-secondary-lt overflow-auto p-4" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px;">
                    <div class="d-flex flex-column bg-white shadow-lg transition-all"
                         style="
                            width: {{ $paperSize == 'k80' ? '80mm' : ($paperSize == 'k57' ? '57mm' : ($paperSize == 'a5' ? '148mm' : '210mm')) }};
                            min-height: {{ $paperSize == 'k80' || $paperSize == 'k57' ? 'auto' : ($paperSize == 'a5' ? '210mm' : '297mm') }};
                            transform-origin: top center;
                         ">
                        <iframe id="preview-frame" srcdoc='{{ $render }}' class="w-100 flex-fill border-0"
                                style="min-height: {{ $paperSize == 'k80' || $paperSize == 'k57' ? '300px' : '100%' }}; display: block;"></iframe>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="d-flex flex-column justify-content-center align-items-center h-100 p-4 text-center">
                    <div class="empty-img mb-4">
                        <div class="bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                            {{ tabler_icon('file-invoice', ['class' => 'icon-xl text-primary', 'style' => 'width: 64px; height: 64px;']) }}
                        </div>
                    </div>
                    <h2 class="h2 fw-bold text-dark mb-2">{{ trans('modules/print-forms::editor.select_form_to_preview') }}</h2>
                    <p class="text-muted fs-3 mx-auto mb-4 max-w-md">
                        {{ trans('modules/print-forms::editor.select_form_desc') }}
                    </p>
                    @can('print-forms.forms.create')
                    <button class="btn btn-primary btn-lg px-4"
                            wire:click="$dispatch('show-modal-create-form', { id: 0, type: '{{ $tab }}' })">
                        {{ tabler_icon('plus', ['class' => 'icon me-2']) }}
                        {{ trans('modules/print-forms::editor.create_new_form') }}
                    </button>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    @livewire('modules/print-forms::editor.modal.modal-create-form')

</div>
