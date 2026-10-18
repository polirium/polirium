<div>
    {{-- A Bootstrap modal that is always on the page: app.js opens it on click
         and the drafts load while it fades in (see show-modal-draft-list). --}}
    <x-ui::modal id="modal-draft-list" :header="__('modules/product::payment.draft_list')" class="modal-lg modal-dialog-centered modal-dialog-scrollable">
        @if($drafts && $drafts->count() > 0)
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ trans('modules/product::product.invoice_code') }}</th>
                            <th>{{ __('Chi nhánh') }}</th>
                            <th>{{ trans('modules/product::product.customer') }}</th>
                            <th class="text-end">{{ trans('modules/product::product.total_amount') }}</th>
                            <th>{{ trans('modules/product::product.creator') }}</th>
                            <th class="text-end">{{ trans('modules/product::product.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drafts as $draft)
                            <tr>
                                <td>
                                    <span class="font-weight-medium">{{ $draft->code }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-azure-lt">
                                        {{ $draft->branch?->name ?? 'Branch #' . $draft->branch_id }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $draft->customer?->name ?? 'Khách lẻ' }}</div>
                                    @if($draft->customer?->phone)
                                        <div class="text-muted small">{{ $draft->customer->phone }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="text-primary font-weight-bold">
                                        {{ core_number_format($draft->value) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $draft->author?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-list flex-nowrap justify-content-end">
                                        {{-- Nút Mở --}}
                                        <x-ui::button
                                            color="primary"
                                            size="sm"
                                            icon="external-link"
                                            :ghost="true"
                                            wire:click="openDraft({{ $draft->id }})"
                                            title="{{ __('modules/product::payment.open_draft') }}">
                                        </x-ui::button>

                                        {{-- Nút Xóa --}}
                                        <x-ui::button
                                            color="danger"
                                            size="sm"
                                            icon="trash"
                                            :outline="true"
                                            wire:click="deleteDraft({{ $draft->id }})"
                                            wire:confirm="{{ __('modules/product::payment.confirm_delete_draft') }}"
                                            title="{{ __('modules/product::payment.delete_draft') }}">
                                        </x-ui::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($drafts->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $drafts->onEachSide(1)->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
        @elseif($drafts)
            <div class="text-center py-5">
                <div class="empty-icon mb-3">
                    {!! tabler_icon('file-stack', ['class' => 'icon icon-lg opacity-25', 'style' => 'width: 48px; height: 48px;']) !!}
                </div>
                <p class="empty-title">{{ __('modules/product::payment.draft_empty') }}</p>
            </div>
        @endif

        <x-slot name="footer">
            <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                {{ __('core/base::general.close') }}
            </button>
        </x-slot>
    </x-ui::modal>
</div>
