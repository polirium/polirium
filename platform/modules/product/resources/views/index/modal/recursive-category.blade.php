@foreach ($list as $item)
    <option value="{{ $item->id }}">{{ $dash }} {{ $item->name }}</option>
    @if ($item->childs?->count() > 0)
        @include('modules/product::index.modal.recursive-category', [
            "list" => $item->childs,
            "dash" => "-{$dash}",
        ])
    @endif
@endforeach
