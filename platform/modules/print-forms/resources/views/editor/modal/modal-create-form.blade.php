<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-form" :header="trans('modules/print-forms::editor.' . ($editor_id ? 'edit' : 'create'))" class="modal-xl">
            <x-ui::errors />
            <div class="row g-0 h-100" style="min-height: 80vh;">
                <!-- Left Sidebar: Controls & Variables -->
                <div class="col-lg-3 border-end bg-light d-flex flex-column h-100 p-0" style="max-height: 80vh; overflow-y: auto;">
                    <div class="border-bottom bg-white p-3">
                        <label class="form-label required">{{ trans('modules/print-forms::editor.form_name_label') }}</label>
                        <x-ui.form.input
                                         wire:model="name"
                                         :placeholder="trans('modules/print-forms::editor.enter_form_name')"
                                         class="mb-3" />

                        <label class="form-label required">{{ trans('modules/print-forms::editor.paper_size') }}</label>
                        <select class="form-select mb-2" wire:model.live="paperSize">
                            <option value="a4">{{ trans('modules/print-forms::editor.size_a4') }}</option>
                            <option value="a5">{{ trans('modules/print-forms::editor.size_a5') }}</option>
                            <option value="k80">{{ trans('modules/print-forms::editor.size_k80') }}</option>
                            <option value="k57">{{ trans('modules/print-forms::editor.size_k57') }}</option>
                        </select>
                        <small class="text-muted d-block mb-3">
                            {{ trans('modules/print-forms::editor.select_paper_size') }}
                        </small>
                    </div>

                    <div class="flex-fill p-3">
                        <label class="form-label fw-bold mb-2">{{ trans('modules/print-forms::editor.variable_list') }}</label>
                        <p class="small text-muted mb-3">{{ trans('modules/print-forms::editor.click_to_copy') }}</p>

                        <div class="accordion" id="accordionVariables">
                            @php
                                $groups = collect($this->variables)->groupBy('group');
                            @endphp

                            @foreach ($groups as $group => $vars)
                                <div class="accordion-item mb-1 overflow-hidden rounded border">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed text-dark fs-5 bg-white px-3 py-2 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $group }}">
                                            @if ($group == 'store_info')
                                                {{ tabler_icon('building-store', ['class' => 'icon-sm me-2 text-primary']) }}
                                            @elseif($group == 'order_info')
                                                {{ tabler_icon('file-invoice', ['class' => 'icon-sm me-2 text-primary']) }}
                                            @elseif($group == 'customer_info')
                                                {{ tabler_icon('user', ['class' => 'icon-sm me-2 text-primary']) }}
                                            @elseif($group == 'items_info')
                                                {{ tabler_icon('list', ['class' => 'icon-sm me-2 text-primary']) }}
                                            @else
                                                {{ tabler_icon('calculator', ['class' => 'icon-sm me-2 text-primary']) }}
                                            @endif

                                            {{ trans('modules/print-forms::editor.' . $group) }}
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $group }}" class="accordion-collapse collapse" data-bs-parent="#accordionVariables">
                                        <div class="accordion-body bg-light p-2">
                                            <div class="d-flex flex-column gap-2">
                                                @foreach ($vars as $var)
                                                    <div class="variable-item d-flex align-items-center justify-content-between hover-shadow-sm user-select-none cursor-pointer rounded border bg-white p-2"
                                                         draggable="true"
                                                         ondragstart="event.dataTransfer.setData('text/plain', '{{ $var['key'] }}')"
                                                         onclick="navigator.clipboard.writeText('{{ $var['key'] }}'); window.dispatchEvent(new CustomEvent('notify', {detail: {title: '{{ trans('modules/print-forms::editor.copied') }}', icon: 'check'}}))"
                                                         title="{{ trans('modules/print-forms::editor.click_to_copy_tooltip') }}">
                                                        <span class="fw-medium text-dark small font-monospace">{{ $var['key'] }}</span>
                                                        <span class="text-muted ms-2" style="font-size: 10px;">{{ $var['desc'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right: Editor -->
                <div class="col-lg-9 d-flex flex-column h-100 position-relative">
                    <div class="flex-fill h-100 position-relative p-0" style="min-height: 70vh;">
                        <div class="d-flex justify-content-between align-items-center border-bottom bg-white px-3 py-2">
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2">{{ trans('modules/print-forms::editor.edit_content') }}</span>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0"
                                        data-bs-toggle="popover"
                                        data-bs-html="true"
                                        data-bs-trigger="focus"
                                        title="{{ trans('modules/print-forms::editor.formatting_guide') }}"
                                        data-bs-content="{{ trans('modules/print-forms::editor.formatting_guide_content') }}">
                                    {{ tabler_icon('help-circle', ['class' => 'icon-sm']) }}
                                </button>
                            </div>

                            <div>
                                <button type="button" class="btn btn-sm {{ $showPreview ? 'btn-primary' : 'btn-outline-secondary' }}"
                                        wire:click="togglePreview">
                                    @if ($showPreview)
                                        {{ tabler_icon('edit', ['class' => 'icon-sm me-1']) }} {{ trans('modules/print-forms::editor.edit_content') }}
                                    @else
                                        {{ tabler_icon('eye', ['class' => 'icon-sm me-1']) }} {{ trans('modules/print-forms::editor.preview') }}
                                    @endif
                                </button>
                            </div>
                        </div>

                        <div wire:key="preview-pane" class="position-absolute w-100 h-100 d-flex justify-content-center {{ $showPreview ? '' : 'd-none' }} start-0 top-0 overflow-auto bg-white p-4" style="z-index: 100; margin-top: 45px;">
                            <div class="border bg-white shadow-lg"
                                 style="
                                        width: {{ $paperSize == 'k80' ? '80mm' : ($paperSize == 'k57' ? '57mm' : ($paperSize == 'a5' ? '148mm' : '210mm')) }};
                                        min-height: {{ $paperSize == 'a5' ? '210mm' : '297mm' }};
                                     ">
                                <iframe srcdoc="{{ $previewHTML }}" class="w-100 h-100 border-0"></iframe>
                            </div>
                        </div>

                        <div key="editor-pane" class="h-100 w-100 {{ !$showPreview ? '' : 'd-none' }}">
                            <x-form::editor wire:model="content" class="h-100" />
                        </div>
                    </div>
                </div>

                <input type="hidden" wire:model="type" />
                <input type="hidden" wire:model="user_id" />
            </div>

            <x-slot:footer>
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <span class="text-muted small">{{ trans('modules/print-forms::editor.changes_applied') }}</span>
                    <div>
                        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                            {{ trans('core/base::general.cancel') }}
                        </button>
                        @canany(['print-forms.forms.create', 'print-forms.forms.edit'])
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    {{ trans('core/base::general.save') }}
                                </span>
                                <span wire:loading wire:target="save">
                                    <i class="ti ti-loader-2 icon-spin me-1"></i>
                                    {{ trans('core/base::general.saving') }}
                                </span>
                            </button>
                        @endcanany
                    </div>
                </div>
            </x-slot:footer>
        </x-ui::modal>
    </form>
    <style>
        .hover-shadow-sm:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-color: var(--tblr-primary) !important;
        }

        .variable-item:active {
            background-color: var(--tblr-primary-lt) !important;
        }
    </style>
</div>
