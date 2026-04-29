<div class="row">
    <div class="col-md-6 col-12">
        <fieldset class="form-fieldset">
            <div class="row">
                <div class="col-md-4 d-flex align-items-center mb-3">
                    <p class="my-auto">{{ trans('modules/product::product.customer_payment') }}</p>
                </div>
                <div class="col-md-2 text-center mb-3">
                    @if ($total_payment)
                        <button type="button" class="btn btn-outline-default btn-icon btn-pill"
                            wire:click="$dispatch('show-modal-payment-type-{{ $tab_selected }}', {value: {{ $total_payment }}, tab_selected: '{{ $tab_selected }}'})">
                            {{ tabler_icon("credit-card") }}
                        </button>
                    @endif
                </div>
                <div class="col-md-6 text-end mb-3">
                    <x-form::currency wire:model.live.debounce.500ms="payment.value_payment" class="text-end" />

                    @if (count((array) ($payment['type_payment'] ?? [])) > 1)
                        <span>{{ $methods_payment }}</span>
                    @endif
                </div>

                <div class="col-md-4 d-flex align-items-center mb-3">
                    <p class="my-auto">{{ trans('modules/product::product.cod_amount') }}</p>
                </div>
                <div class="col-md-2 text-center mb-3">

                </div>
                <div class="col-md-6 text-end mb-3">
                    <span>{{ core_number_format($total_payment) }}</span>
                </div>

                @if (($payment['value_payment'] ?? 0) > (int)$total_payment)
                    <div class="col-md-6 d-flex align-items-center mb-3">
                        <p class="my-auto">{{ trans('modules/product::product.change_amount') }}</p>
                    </div>
                    <div class="col-md-6 text-end mb-3">
                        <span>{{ core_number_format(($payment['value_payment'] ?? 0) - (int)$total_payment) }}</span>
                    </div>
                @endif
            </div>
        </fieldset>
    </div>

    <div class="col-md-6 col-12">
        <fieldset class="form-fieldset">
            <div class="row align-items-end mb-3">
                <div class="col-10">
                    <x-form::select
                        :options="$lists['partner_deliveries']"
                        wire:model.live="payment_delivery.partner_delivery_id"
                        wire:key="partner-delivery-select-{{ $payment_delivery['partner_delivery_id'] ?? 'null' }}"
                        :label="trans('modules/product::product.delivery_partner.label')"
                    />
                </div>
                <div class="col-2 pl-0">
                    <button type="button"
                        wire:click="$dispatch('show-modal-create-partner-delivery', { id: {{ $payment_delivery['partner_delivery_id'] ?? 0 }} })"
                        @class([
                            "btn btn-icon w-100",
                            "btn-ghost-success" => !($payment_delivery['partner_delivery_id'] ?? null),
                            "btn-warning" => ($payment_delivery['partner_delivery_id'] ?? null),
                        ]) >
                        @if ($payment_delivery['partner_delivery_id'] ?? null)
                            {{ tabler_icon("edit") }}
                        @else
                            {{ tabler_icon("plus") }}
                        @endif
                    </button>
                </div>
            </div>

            @if ($payment_delivery['partner_delivery_id'] ?? null)
                <div class="mb-3">
                    <x-form::select :options="$lists['type_delivery']" wire:model="payment_delivery.type" :label="trans('modules/product::product.service_type')" />
                </div>

                <div class="mb-3">
                    <x-form::currency wire:model="payment_delivery.value" :label="trans('modules/product::product.delivery_fee')" />
                </div>

                <div class="mb-3">
                    <x-form::input wire:model="payment_delivery.code" :label="trans('modules/product::product.delivery_code')" />
                </div>

                <div class="mb-3">
                    <x-form::input type="date" wire:model="payment_delivery.date" :label="trans('modules/product::product.delivery_time')" />
                </div>

                <div class="mb-3">
                    <x-form::select :options="$lists['status_delivery']" wire:model="payment_delivery.status" :label="trans('modules/product::product.delivery_status')" />
                </div>
            @endif
        </fieldset>
    </div>
</div>
