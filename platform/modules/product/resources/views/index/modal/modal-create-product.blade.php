@php
    $typeLabel = match ($product['type'] ?? 'product') {
        'service' => trans('modules/product::product.edit_or_create_service'),
        'combo' => trans('modules/product::product.edit_or_create_combo'),
        default => trans('modules/product::product.edit_or_create_product'),
    };
    $actionLabel = $readonly ? trans('core/base::general.view') : ($product_id ? trans('modules/product::product.edit') : trans('modules/product::product.create_new'));
    $headerTitle = $actionLabel . ' ' . $typeLabel;
@endphp
<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-product" :header="$headerTitle" class="modal-xl">
            @if ($message_alert = session('message'))
                <x-ui::alert :color="$message_alert['color']" :label="$message_alert['message']" />
            @endif

            <x-ui::tab>
                <x-slot name="header">
                    <x-ui::tab.header wire:click="$set('tab', 1)" :active="$tab == 1" :label="trans('modules/product::product.information')" />
                    <x-ui::tab.header wire:click="$set('tab', 2)" :active="$tab == 2" :label="trans('modules/product::product.detailed_description')" />
                    @if (in_array($product['type'] ?? 'product', ['product', 'combo']))
                        <x-ui::tab.header wire:click="$set('tab', 3)" :active="$tab == 3" :label="trans('modules/product::product.components')" />
                    @endif
                </x-slot>

                <x-ui::tab.item :show="$tab == 1">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <x-ui.form.input :label="trans('modules/product::product.product_code')" wire:model="product.code" :disabled="$readonly" />
                            </div>
                            <div class="mb-3">
                                <x-ui.form.input :label="trans('modules/product::product.product_name')" wire:model="product.name" :disabled="$readonly" />
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="ui-form-label d-block mb-2">{{ trans('modules/product::product.product_group') }}</label>
                                    <div class="input-group">
                                        <select class="form-select" wire:model.live="product.category_id" @disabled($readonly)>
                                            <option value="">-- {{ trans('modules/product::product.none') }} --</option>
                                            @if (count($categories) > 0)
                                                @include('modules/product::index.modal.recursive-category', ['list' => $categories, 'dash' => ''])
                                            @endif
                                        </select>
                                        @if (!$readonly)
                                            <button type="button" class="btn btn-icon" wire:click="$dispatch('show-modal-create-category', { id: {{ $product['category_id'] ?? 0 }} })">
                                                @if ($product['category_id'] ?? null)
                                                    {{ tabler_icon('pencil') }}
                                                @else
                                                    {{ tabler_icon('plus') }}
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="ui-form-label d-block mb-2">{{ trans('modules/product::product.trademark') }}</label>
                                    <div class="input-group">
                                        <select class="form-select" wire:model.live="product.trademark_id" @disabled($readonly)>
                                            <option value="">-- {{ trans('core/base::general.select_item') }} --</option>
                                            @foreach ($trademarks as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @if (!$readonly)
                                            <button type="button"
                                                    class="btn btn-icon"
                                                    wire:click="$dispatch('show-modal-create-list', { id: {{ $product['trademark_id'] ?? 0 }}, 'title': '{{ trans('modules/product::product.trademark') }}'})">
                                                @if ($product['trademark_id'] ?? null)
                                                    {{ tabler_icon('pencil') }}
                                                @else
                                                    {{ tabler_icon('plus') }}
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                @if (in_array($product['type'] ?? 'product', ['product', 'combo']))
                                    <div class="col-md-4 mb-3">
                                        <label class="ui-form-label d-block mb-2">{{ trans('modules/product::product.location') }}</label>
                                        <div class="input-group">
                                            <select class="form-select" wire:model.live="product.shelve_id" @disabled($readonly)>
                                                <option value="">-- {{ trans('core/base::general.select_item') }} --</option>
                                                @foreach ($shelves as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @if (!$readonly)
                                                <button type="button"
                                                        class="btn btn-icon"
                                                        wire:click="$dispatch('show-modal-create-list', { id: {{ $product['shelve_id'] ?? 0 }}, 'title': '{{ trans('modules/product::product.location') }}', 'tbl': 'shelves', 'model': 'Shelve' })">
                                                    @if ($product['shelve_id'] ?? null)
                                                        {{ tabler_icon('pencil') }}
                                                    @else
                                                        {{ tabler_icon('plus') }}
                                                    @endif
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            @if (($product['type'] ?? 'product') !== 'service')
                                @can('products.view-cost')
                                    <div class="mb-3">
                                        <x-ui.form.input
                                                         :label="trans('modules/product::product.cost_price')"
                                                         wire:model="product.cost"
                                                         x-init="if (window.PoliriumProduct) window.PoliriumProduct.initCurrencyInput($el.querySelector('input') || $el, $wire)"
                                                         data-inputmask-currency
                                                         :disabled="$readonly" />
                                    </div>
                                @endcan
                            @endif

                            <div class="mb-3">
                                <x-ui.form.input
                                                 :label="trans('modules/product::product.selling_price')"
                                                 wire:model="product.price"
                                                 x-init="if (window.PoliriumProduct) window.PoliriumProduct.initCurrencyInput($el.querySelector('input') || $el, $wire)"
                                                 data-inputmask-currency
                                                 :disabled="$readonly" />
                            </div>

                            @if (($product['type'] ?? 'product') !== 'service')
                                <div class="mb-3">
                                    <label class="ui-form-label d-block mb-2">{{ trans('modules/product::product.weight') }}</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" wire:model="product.weight" @disabled($readonly)>
                                        <select class="form-select" wire:model="product.weight_type" style="max-width: 100px;" @disabled($readonly)>
                                            <option value="gram">gram</option>
                                            <option value="kg">kg</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                @if (!($product['unit'] ?? null))
                                    <x-ui.form.checkbox
                                                        :label="trans('modules/product::product.direct_sale')"
                                                        wire:model="product.allows_sale"
                                                        value="1"
                                                        :disabled="$readonly" />
                                @endif
                            </div>

                            <div class="mb-3">
                                <x-ui.form.select
                                                  wire:model="branches"
                                                  :options="$lists['branches']"
                                                  multiple
                                                  :label="trans('modules/product::product.branch')"
                                                  :hint="trans('modules/product::product.default_all_branches')"
                                                  :disabled="$readonly" />
                            </div>
                        </div>

                        <div class="col-md-12">
                            <x-ui::accordion id="accordion-unit">
                                <x-ui::accordion.item id="arrco-1" :title="trans('modules/product::product.unit_of_measurement')" parent="accordion-unit">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <x-ui.form.input
                                                             :label="trans('modules/product::product.base_unit')"
                                                             wire:model.live="product.unit"
                                                             :placeholder="trans('modules/product::product.base_unit_placeholder')"
                                                             :disabled="$readonly" />
                                        </div>
                                        <div class="col-md-6 pt-4">
                                            @if ($product['unit'] ?? null)
                                                <x-ui.form.checkbox
                                                                    :label="trans('modules/product::product.direct_sale')"
                                                                    wire:model="product.allows_sale"
                                                                    value="1"
                                                                    :disabled="$readonly" />
                                            @endif
                                        </div>
                                    </div>

                                    @if ($product['unit'] ?? null)
                                        <div class="mt-3">
                                            @if (!$readonly)
                                                <button type="button" wire:click="addUnit" class="btn btn-ghost-info">
                                                    {{ tabler_icon('plus') }}
                                                    {{ trans('modules/product::product.add_unit') }}
                                                </button>
                                            @endif
                                        </div>

                                        @if (count($units) > 0)
                                            <div class="table-responsive mt-3">
                                                <table class="table-vcenter card-table table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ trans('modules/product::product.unit_name') }}</th>
                                                            <th>{{ trans('modules/product::product.conversion_value') }}</th>
                                                            <th>{{ trans('modules/product::product.unit_price') }}</th>
                                                            <th>{{ trans('modules/product::product.product_code_short') }}</th>
                                                            <th></th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($units as $key => $item)
                                                            <tr>
                                                                <td>
                                                                    <x-ui.form.input wire:model="units.{{ $key }}.name" compact :disabled="$readonly" />
                                                                </td>
                                                                <td>
                                                                    <x-ui.form.input wire:model="units.{{ $key }}.conversion_value" compact :disabled="$readonly" />
                                                                </td>
                                                                <td>
                                                                    <x-ui.form.input wire:model="units.{{ $key }}.price" compact :disabled="$readonly" />
                                                                </td>
                                                                <td>
                                                                    <x-ui.form.input wire:model="units.{{ $key }}.code" compact :disabled="$readonly" />
                                                                </td>
                                                                <td>
                                                                    <x-ui.form.checkbox wire:model="units.{{ $key }}.allows_sale" value="1" :label="trans('modules/product::product.direct_sale')" compact :disabled="$readonly" />
                                                                </td>
                                                                <td>
                                                                    @if (!$readonly)
                                                                        <button type="button" wire:click="removeUnit({{ $key }})" class="btn w-100 btn-icon btn-ghost-danger">
                                                                            {{ tabler_icon('trash') }}
                                                                        </button>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    @endif
                                </x-ui::accordion.item>
                            </x-ui::accordion>
                        </div>
                    </div>
                </x-ui::tab.item>

                <x-ui::tab.item :show="$tab == 2">
                    <x-ui::card :header="trans('modules/product::product.inventory_level')">
                        <div class="row">
                            <div class="col-md-6">
                                <x-ui.form.input
                                                 wire:model="product.min_quantity"
                                                 :label="trans('modules/product::product.minimum')"
                                                 x-init="if (window.PoliriumProduct) window.PoliriumProduct.initCurrencyInput($el.querySelector('input') || $el, $wire)"
                                                 data-inputmask-currency
                                                 :disabled="$readonly" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.form.input
                                                 wire:model="product.max_quantity"
                                                 :label="trans('modules/product::product.maximum')"
                                                 x-init="if (window.PoliriumProduct) window.PoliriumProduct.initCurrencyInput($el.querySelector('input') || $el, $wire)"
                                                 data-inputmask-currency
                                                 :disabled="$readonly" />
                            </div>
                        </div>
                    </x-ui::card>

                    <br>

                    <x-ui::card :header="trans('modules/product::product.description')">
                        <x-ui.form.textarea wire:model="product.description" :disabled="$readonly" />
                    </x-ui::card>

                    <br>

                    <x-ui::card :header="trans('modules/product::product.note')">
                        <x-ui.form.textarea wire:model="product.note" :disabled="$readonly" />
                    </x-ui::card>
                </x-ui::tab.item>

                <x-ui::tab.item :show="$tab == 3">
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">
                            {{ trans('modules/product::product.search_product') }}
                        </label>
                        <div class="position-relative" x-data="{ show: false }">
                            <input
                                   type="text"
                                   class="form-control"
                                   wire:model.live.debounce.500ms="search"
                                   @focus="show = true"
                                   @input="show = true"
                                   @click="show = true"
                                   @click.outside="show = false"
                                   placeholder="{{ trans('core/base::general.search_placeholder') }}"
                                   @disabled($readonly) />
                            @if (isset($lists['products']) && count($lists['products']) > 0)
                                <div class="list-group list-group-flush bg-light position-absolute w-100 custom-scrollbar shadow"
                                     style="z-index: 1050; max-height: 300px; overflow-y: auto;"
                                     x-show="show"
                                     x-cloak>
                                    @foreach ($lists['products'] as $product)
                                        <button type="button" class="list-group-item list-group-item-action" wire:click="addElement({{ $product->id }}); show = false;">
                                            <p class="mb-0">{{ $product->name }}</p>
                                            <small class="text-muted">{{ $product->code }}</small>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table-vcenter card-table table">
                            <thead>
                                <tr>
                                    <th>{{ trans('modules/product::product.stt') }}</th>
                                    <th>{{ trans('modules/product::product.product_code_short') }}</th>
                                    <th>{{ trans('modules/product::product.product_name') }}</th>
                                    <th>{{ trans('modules/product::product.quantity') }}</th>
                                    <th>{{ trans('modules/product::product.cost_price') }}</th>
                                    <th>{{ trans('modules/product::product.total') }}</th>
                                    <th>{{ trans('modules/product::product.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($elements as $elementId => $element)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $element['product']['code'] }}</td>
                                        <td>{{ $element['product']['name'] }}</td>
                                        <td>
                                            <x-ui.form.input
                                                             wire:model.live="elements.{{ $elementId }}.qty"
                                                             type="number"
                                                             min="1"
                                                             compact
                                                             :disabled="$readonly" />
                                        </td>
                                        <td>{{ number_format($element['product']['price']) }}</td>
                                        <td>{{ number_format($element['price']) }}</td>
                                        <td>
                                            <button
                                                    type="button"
                                                    class="btn btn-sm btn-icon btn-ghost-danger"
                                                    wire:click="removeElement({{ $elementId }})"
                                                    @disabled($readonly)>
                                                {{ tabler_icon('trash') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted py-4 text-center">
                                            {{ trans('modules/product::product.no_components') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui::tab.item>
            </x-ui::tab>

            <x-slot:footer>
                @if (!$readonly)
                    <button type="submit" class="btn btn-success">
                        {{ tabler_icon('device-floppy') }}
                        {{ trans('modules/product::product.save') }}
                    </button>
                @else
                    <button type="button" class="btn" data-bs-dismiss="modal">
                        {{ trans('core/base::general.close') }}
                    </button>
                @endif
            </x-slot:footer>
        </x-ui::modal>
    </form>

    @livewire('modules/product::index.modal.modal-create-category')
    @livewire('modules/product::index.modal.modal-create-list')
</div>
