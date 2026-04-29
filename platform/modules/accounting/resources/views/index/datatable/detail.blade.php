@php
    // Use context passed from PowerGrid fields, fallback to safe detection
    $type = $row->type_context ?? 'receipt';

    // For model class, use the passed string or try to guess.
    // If $row is stdClass, we can't get class from it.
    $model = $row->model_class ?? 'Polirium\\Modules\\Accounting\\Http\\Model\\Receipt';
@endphp

<x-ui::table class="table-bordered">
    <tr>
        <td><b>{{ trans('modules/accounting::accounting.' . $type . '.code') }}:</b></td>
        <td style="width: 20%">{{ $row->code }}</td>
        <td><b>{{ trans('modules/accounting::accounting.branch') }}</b></td>
        <td>{{ $row->branch_name }}</td>
        <td rowspan="999">
            <b>{{ trans('core/base::general.note') }}:</b>
            <p>{{ $row->note }}</p>
        </td>
    </tr>

    <tr>
        <td><b>{{ trans('modules/accounting::accounting.date') }}:</b></td>
        <td>{{ core_format_date($row->date) }}</td>
        <td><b>{{ trans('modules/accounting::accounting.type.name') }}</b></td>
        <td>{{ $row->type_name }}</td>
    </tr>

    <tr>
        <td><b>{{ trans('modules/accounting::accounting.value') }}:</b></td>
        <td>{{ core_number_format($row->value) }}</td>
    </tr>

    <tr>
        <td><b>{{ trans('modules/accounting::accounting.pay_person.name') }}:</b></td>
        <td>
            @php
                try {
                    // finance_name is already added in fields()
                    $financeName = $row->finance_name ?? null;
                } catch (\Throwable $e) {
                    $financeName = null;
                }
            @endphp
            {{ $financeName }}
        </td>
        <td><b>{{ trans('modules/accounting::accounting.user_created') }}</b></td>
        <td>{{ $row->user_created_name }}</td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td><b>{{ trans('modules/accounting::accounting.user') }}</b></td>
        <td>{{ $row->user_name }}</td>
    </tr>

    <tr>
        <td><b>{{ trans('modules/accounting::accounting.branch') }}:</b></td>
        <td>{{ $row->branch_name }}</td>
    </tr>
</x-ui::table>

<div class="action-buttons mt-3">
    <button
        class="action-btn edit icon-only"
        data-tooltip="{{ trans('modules/accounting::accounting.edit') }}"
        wire:click="$dispatch('show-modal-create-accounting', { model: '{{ addslashes($model) }}', type: '{{ $type }}', id: {{ $row->id }} })"
        aria-label="{{ trans('modules/accounting::accounting.edit') }}"
    >
        {!! tabler_icon('pencil', ['class' => 'icon']) !!}
        <span class="action-text">{{ trans('modules/accounting::accounting.edit') }}</span>
    </button>
</div>
