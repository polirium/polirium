<div>
    @can('accountings.edit')
        @if ($isEditing)
            <div class="d-flex flex-column gap-2">
                <textarea wire:model="noteContent" class="form-control" rows="3"></textarea>
                <div class="d-flex justify-content-end gap-2">
                    <x-ui::button
                                  color="success"
                                  size="sm"
                                  icon="device-floppy"
                                  wire:click="save">
                        {{ trans('modules/accounting::accounting.save') }}
                    </x-ui::button>
                    <x-ui::button
                                  color="secondary"
                                  size="sm"
                                  icon="x"
                                  wire:click="cancel">
                        {{ trans('modules/accounting::accounting.cancel') }}
                    </x-ui::button>
                </div>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-start group-hover-target">
                <div class="fst-italic text-break mb-0">{{ $currentNote ?: 'Không có ghi chú' }}</div>
                <x-ui::button
                              color="ghost"
                              size="sm"
                              icon="pencil"
                              class="text-muted"
                              wire:click="edit"
                              title="{{ trans('modules/accounting::accounting.edit') }}" />
            </div>
        @endif
    @endcan
</div>
