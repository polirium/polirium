<div>
    <div class="row">
        <div class="col-md-8">
            <x-ui::card>
                @if ($state['status'] == 'temp')
                    <div class="w-100">
                        <x-form::autocomplete wire:model.live="search">
                            @foreach ($lists['products'] as $item)
                                <x-form::autocomplete.item wire:click="selectProduct({{ $item['id'] }})" class="w-100">
                                    <b>{{ $item['name'] }} - {{ $item['unit'] }}</b> <br>
                                    <span>{{ $item['code'] }}@can('vendors.transfers.view-price') - Giá: {{ core_number_format($item['cost']) }}@endcan</span> <br>
                                    <span>{{ trans('modules/vendor::purchase.stock_label') }} {{ isset($item['amount']) ? $item['amount'] : 0 }}</span>
                                </x-form::autocomplete.item>
                            @endforeach
                        </x-form::autocomplete>
                    </div>
                @endif

                <br>
                <br>

                <x-ui::table striped class="table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>{{ trans('modules/product::product.code') }}</th>
                            <th>{{ trans('modules/product::product.name') }}</th>
                            <th>{{ trans('modules/product::product.unit') }}</th>
                            <th>{{ trans('modules/vendor::purchase.stock') }}</th>
                            <th>{{ trans('modules/vendor::purchase.to_branch_stock') }}</th>
                            <th>{{ trans('modules/vendor::purchase.quantity_transfer') }}</th>
                            @can('vendors.transfers.view-price')
                            <th>{{ trans('modules/vendor::purchase.transfer_price') }}</th>
                            <th>{{ trans('modules/vendor::purchase.total_amount_column') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $key => $item)
                            <tr data-product-id="{{ $item['product_id'] }}">
                                <td>
                                    @if ($state['status'] == 'temp')
                                        <x-ui::button
                                            color="danger"
                                            size="sm"
                                            icon="trash"
                                            :ghost="true"
                                            wire:click="removeProduct({{ $key }})"
                                            title="{{ trans('core/base::general.delete') }}"
                                        />
                                    @endif
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ isset($item['product']['code']) ? $item['product']['code'] : null }}</td>
                                <td>
                                    <span>{{ isset($item['product']['name']) ? $item['product']['name'] : null }}</span>
                                    @if ($state['status'] == 'temp')
                                        <x-form::input :placeholder="trans('core/base::general.note')" wire:model="products.{{ $key }}.note" class="form-control-sm" />
                                    @else
                                        {{ $item['note'] }}
                                    @endif
                                </td>
                                <td>{{ isset($item['product']['unit']) ? $item['product']['unit'] : null }}</td>
                                <td>{{ isset($item['product']['amount']) ? $item['product']['amount'] : 0 }}</td>
                                <td></td>
                                <td>
                                    @if ($state['status'] == 'temp')
                                        <x-form::input type="number" wire:model.live="products.{{ $key }}.amount" />
                                    @else
                                        {{ core_number_format($item['amount']) }}
                                    @endif
                                </td>
                                @can('vendors.transfers.view-price')
                                <td>
                                    @if ($state['status'] == 'temp')
                                        <x-form::currency wire:model.live="products.{{ $key }}.price" />
                                    @else
                                        {{ core_number_format($item['price']) }}
                                    @endif
                                </td>
                                <td>
                                    {{ core_number_format($item['value']) }}
                                </td>
                                @endcan
                            </tr>
                        @empty

                        @endforelse
                    </tbody>
                </x-ui::table>
            </x-ui::card>
        </div>

        <div class="col-md-4">
            <x-ui::card>
                <x-ui::table class="table-bordered">
                    @if ($state['status'] == 'temp')
                        <tr>
                            <td class="w-50"><b>{{ trans('modules/vendor::transfer.code') }}:</b></td>
                            <td class="text-end">
                                <x-form::input wire:model="state.code" />
                            </td>
                        </tr>

                        <tr>
                            <td><b>{{ trans('core/base::general.status') }}:</b></td>
                            <td class="text-end">{{ $transfer->status_name }}</td>
                        </tr>

                        <tr>
                            <td><b>{{ trans('modules/vendor::transfer.amount') }}:</b></td>
                            <td class="text-end">{{ array_sum(array_column($products, 'amount')) }}</td>
                        </tr>

                        <tr>
                            <td><b>{{ trans('modules/vendor::transfer.to_branch') }}:</b></td>
                            <td class="text-end">
                                <x-form::select :options="$lists['branches']" tomselect wire:model.live="state.to_branch_id" />
                            </td>
                        </tr>

                        <tr>
                            <td><b>{{ trans('modules/vendor::transfer.date_send') }}:</b></td>
                            <td class="text-end">
                                <x-form::input type="date" wire:model.live="state.date_send" />
                            </td>
                        </tr>

                        <tr>
                            <td colspan="999">
                                <x-form::input wire:model.live="state.note" :label="trans('core/base::general.note')" />

                                <x-ui::errors />
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ trans('modules/vendor::purchase.code_label') }}</td>
                            <td>{{ $transfer->code }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('modules/vendor::purchase.status_label') }}</td>
                            <td>{{ $transfer->status_name }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('modules/vendor::purchase.date_send_label') }}</td>
                            <td>{{ core_format_date($transfer->date_send) }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('modules/vendor::purchase.note_label') }}</td>
                            <td>{{ $transfer->note }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('modules/vendor::purchase.total_quantity_send') }}</td>
                            <td>{{ core_number_format($transfer->products->count()) }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('modules/vendor::purchase.total_quantity_receive') }}</td>
                            <td>{{ core_number_format($transfer->products->sum('amount')) }}</td>
                        </tr>
                    @endif

                    @if ($state['status'] == 'delivery' && (int)$state['to_branch_id'] === user_branch())
                        <tr>
                            <td colspan="999">
                                <x-ui.button color="success" icon="device-floppy" wire:click="save('success')" :label="trans('modules/vendor::transfer.deliveried')" style="width: 100%;" />
                            </td>
                        </tr>
                    @else
                        @if ($state['status'] == 'temp')
                            <tr>
                                <td>
                                    <x-ui.button color="primary" icon="device-floppy" wire:click="save('temp')" :label="trans('modules/vendor::transfer.status.temp')" style="width: 100%;" />
                                </td>
                                <td>
                                    <x-ui.button color="success" icon="device-floppy" wire:click="save('delivery')" :label="trans('modules/vendor::transfer.status.success')" style="width: 100%;" />
                                </td>
                            </tr>
                        @endif
                    @endif
                </x-ui::table>
            </x-ui::card>
        </div>
    </div>
</div>
