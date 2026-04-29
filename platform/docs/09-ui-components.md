# UI Components

## Layouts

### App Layout

Layout chính cho admin pages.

```blade
<x-ui.layouts::app>
    <x-slot:title>Page Title</x-slot:title>
    <x-slot:subtitle>Optional subtitle</x-slot:subtitle>

    <x-slot:actions>
        <x-ui.button color="primary">Action</x-ui.button>
    </x-slot:actions>

    {{-- Content --}}
</x-ui.layouts::app>
```

### Minimal Layout

Layout tối giản cho login, error pages.

```blade
<x-ui.layouts::minimal>
    {{-- Content --}}
</x-ui.layouts::minimal>
```

---

## Cards

### Basic Card

```blade
<x-ui::card>
    Content here
</x-ui::card>
```

### Card with Header

```blade
<x-ui::card header="Card Title">
    Content here
</x-ui::card>
```

---

## Buttons

```blade
{{-- Basic --}}
<x-ui.button>Default</x-ui.button>
<x-ui.button color="primary">Primary</x-ui.button>
<x-ui.button color="success">Success</x-ui.button>
<x-ui.button color="danger">Danger</x-ui.button>
<x-ui.button color="warning">Warning</x-ui.button>

{{-- With icon --}}
<x-ui.button icon="plus" color="success">Create</x-ui.button>

{{-- With label --}}
<x-ui.button icon="device-floppy" color="success" :label="__('Save')" />

{{-- Submit button --}}
<x-ui.button type="submit" color="primary">Submit</x-ui.button>
```

---

## Modals

```blade
<x-ui::modal id="my-modal" header="Modal Title" class="modal-lg">
    {{-- Modal content --}}

    <x-slot:footer>
        <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
    </x-slot:footer>
</x-ui::modal>
```

Open modal với Livewire:

```php
$this->dispatch('show-modal', id: 'my-modal');
```

---

## Tables

```blade
<x-ui::table striped>
    <x-slot:header>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </x-slot:header>

    @foreach($items as $item)
        <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->name }}</td>
            <td>
                <x-ui.button size="sm" color="primary">Edit</x-ui.button>
            </td>
        </tr>
    @endforeach
</x-ui::table>
```

---

## Tabs

```blade
<x-ui::tab>
    <x-slot:headers>
        <x-ui::tab.header wire:click="$set('tab', 1)" :active="$tab == 1" label="Tab 1" />
        <x-ui::tab.header wire:click="$set('tab', 2)" :active="$tab == 2" label="Tab 2" />
    </x-slot:headers>

    <x-ui::tab.item :show="$tab == 1">
        Tab 1 content
    </x-ui::tab.item>

    <x-ui::tab.item :show="$tab == 2">
        Tab 2 content
    </x-ui::tab.item>
</x-ui::tab>
```

---

## Alerts

```blade
<x-ui::alert color="success" label="Success message" />
<x-ui::alert color="warning" label="Warning message" />
<x-ui::alert color="danger" label="Error message" />
<x-ui::alert color="info" label="Info message" />
```

---

## Form Components

### Text Input

```blade
<x-form::text
    name="name"
    label="Name"
    :value="$name"
    required
/>
```

### Select

```blade
<x-form::select
    name="status"
    label="Status"
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    :value="$status"
/>
```

### Select with TomSelect

```blade
<x-form::select
    name="province_id"
    label="Province"
    :options="get_provinces()"
    tomselect
/>
```

### Textarea

```blade
<x-form::textarea
    name="description"
    label="Description"
    rows="5"
/>
```

---

## Errors Component

```blade
<x-ui::errors />
```

Hiển thị validation errors từ `$errors` bag.

---

## Component Namespace

- `x-ui::` - Core UI components (card, modal, table, ...)
- `x-ui.layouts::` - Layout components (app, minimal)
- `x-form::` - Form components (text, select, textarea, ...)
- `x-ui.button` - Button component
