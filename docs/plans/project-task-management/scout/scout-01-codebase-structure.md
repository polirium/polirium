# Polirium ERP Codebase Structure Analysis

**Generated:** 2026-01-17  
**Purpose:** Understanding module structure, patterns, and conventions for building Project Task Management module

---

## 1. MODULE STRUCTURE

### 1.1 Standard Module Directory Structure

```
platform/modules/your-module/
├── composer.json                 # Module metadata & autoloading
├── webpack.mix.js               # Asset compilation (if needed)
├── config/
│   ├── livewire.php            # Livewire component registration
│   ├── menu.php                # Admin menu items
│   ├── permissions.php         # Permission definitions
│   └── [module].php            # Module-specific config
├── database/
│   └── migrations/             # Database migrations
├── helpers/                    # Helper functions (optional)
│   └── common.php
├── public/                     # Published assets
│   ├── images/
│   └── js/
├── resources/
│   ├── lang/
│   │   ├── en/                 # English translations
│   │   │   └── [module].php
│   │   └── vi/                 # Vietnamese translations
│   │       └── [module].php
│   └── views/                  # Blade templates
│       ├── index/
│       │   ├── index.blade.php
│       │   ├── filter-sidebar.blade.php
│       │   ├── datatable/
│       │   │   ├── header.blade.php
│       │   │   ├── footer.blade.php
│       │   │   └── header-actions.blade.php
│       │   └── modal/
│       │       └── modal-create.blade.php
│       └── [feature]/
├── routes/
│   └── web.php                 # Web routes
└── src/
    ├── Http/
    │   ├── Controllers/        # Traditional controllers (minimal use)
    │   ├── Livewire/           # Livewire components (primary)
    │   │   ├── Index/
    │   │   │   ├── Datatable/
    │   │   │   │   └── [Entity]Table.php
    │   │   │   └── Modal/
    │   │   │       └── ModalCreate[Entity]Component.php
    │   │   └── [Feature]/
    │   │       ├── FilterSidebarComponent.php
    │   │       ├── Datatable/
    │   │       └── Modal/
    │   └── Model/              # Eloquent models
    │       ├── [Entity].php
    │       └── [Entity]Group.php
    ├── Imports/                # Excel import classes (optional)
    └── Providers/
        └── [Module]ServiceProvider.php
```

### 1.2 Module Registration (composer.json)

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/composer.json`

```json
{
    "name": "polirium/vendor",
    "description": "Vendor Module - Module for Polirium Core",
    "require": {},
    "autoload": {
        "psr-4": {
            "Polirium\\Modules\\Vendor\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Polirium\\Modules\\Vendor\\Providers\\VendorServiceProvider"
            ]
        }
    }
}
```

**Key Points:**
- PSR-4 namespace: `Polirium\Modules\[ModuleName]\`
- ServiceProvider in `src/Providers/`
- Laravel auto-discovers modules via composer.json

### 1.3 Service Provider Pattern

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Providers/VendorServiceProvider.php`

```php
<?php

namespace Polirium\Modules\Vendor\Providers;

use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\Base\Helpers\BaseHelper;

class VendorServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/vendor')
            ->loadConfigurations(['vendor'])  // Loads vendor.php + livewire, menu, permissions
            ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();
    }

    public function register()
    {
        BaseHelper::autoload(__DIR__ . '/../../helpers');
    }
}
```

**LoadAndPublishDataTrait Methods:**
| Method | Description |
|--------|-------------|
| `setNamespace($namespace)` | Set module namespace (e.g., `modules/vendor`) |
| `loadConfigurations($files)` | Load config files (auto-adds livewire, menu, permissions) |
| `loadViews()` | Load views from `resources/views/` |
| `loadTranslations()` | Load translations from `resources/lang/` |
| `loadRoutes($files)` | Load routes from `routes/` |
| `loadMigrations()` | Load migrations from `database/migrations/` |

---

## 2. DATABASE PATTERNS

### 2.1 Migration Structure

**Example:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/database/migrations/2024_11_06_154144_vendor_create_vendor_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Main entity table
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string("uuid");              // UUID for public reference
            $table->integer('branch_id')->nullable();
            $table->string('code');              // Auto-generated code
            $table->string('name');
            $table->string('vat');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer("province_id")->nullable();
            $table->integer("district_id")->nullable();
            $table->integer("ward_id")->nullable();
            $table->integer("user_created_id")->nullable();
            $table->string('company')->nullable();
            $table->string('status')->default('active');
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('debt', 15, 2)->default(0);
            $table->decimal('total_purchase', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Group table
        Schema::create('vendor_groups', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->string('note')->nullable();
            $table->integer("user_created_id")->nullable();
            $table->timestamps();
        });

        // Pivot table for many-to-many
        Schema::create('vendors_groups_pivot', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_id');
            $table->integer('vendor_group_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('vendor_groups');
        Schema::dropIfExists('vendors_groups_pivot');
    }
};
```

### 2.2 Standard Column Patterns

| Column | Type | Purpose | Example |
|--------|------|---------|---------|
| `id` | `id()` | Primary key | Always present |
| `uuid` | `string('uuid')` | Public reference | `uuid` |
| `code` | `string('code')` | Auto-generated code | `VN001`, `CUS001` |
| `branch_id` | `integer('branch_id')` | Multi-branch support | Foreign key |
| `user_created_id` | `integer('user_created_id')` | Created by | Track creator |
| `status` | `string('status')` | Status | `active`, `inactive`, `temp` |
| `note` | `string('note')->nullable()` | Notes | Optional notes |
| `date` | `date('date')->nullable()` | Date field | Transaction date |
| `timestamps()` | - | created_at, updated_at | Always present |

### 2.3 Date Handling

**Payment Model Example:** `/Users/vingamagic/Developer/php/polirium/platform/modules/accounting/src/Http/Model/Payment.php`

```php
protected $fillable = [
    'uuid',
    'branch_id',
    'code',
    'date',              // DATE column (not datetime)
    'type_id',
    'value',
    'user_id',
    'user_created_id',
    'note',
];
```

**Migration Pattern:**
```php
$table->date('date')->nullable();  // For dates without time
$table->timestamp('due_date')->nullable();  // For timestamps
```

### 2.4 Model Patterns

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/customer/src/Http/Model/Customer.php`

```php
<?php

namespace Polirium\Modules\Customer\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;

class Customer extends BaseModel
{
    protected $table = "customers";

    protected $fillable = [
        "uuid",
        "code",
        "name",
        "phone",
        "birthday",          // DATE column
        "sex",
        "address",
        "type",
        "email",
        "note",
        "status",
        "user_id",
        "branch_id",
    ];

    // Accessor example
    public function getGenderAttribute()
    {
        return match ((int) $this->sex) {
            1 => trans("Nữ"),
            default => trans("Nam"),
        };
    }

    // Many-to-many relationship
    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'customers_pivot_groups', 'customer_id', 'customer_group_id')
            ->withTimestamps()
            ->withPivot(["id"]);
    }

    // BelongsTo relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
```

**BaseModel Features:** `/Users/vingamagic/Developer/php/polirium/platform/core/base/src/Http/Models/BaseModel.php`

```php
<?php

namespace Polirium\Core\Base\Http\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Polirium\Core\Base\Http\Models\Traits\HasUuid;
use Spatie\Activitylog\Traits\LogsActivity;

class BaseModel extends Model
{
    use LogsActivity;
    use HasUuid;

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = new LogOptions();
        $logOptions->logAll();
        $logOptions->logOnlyDirty();
        return $logOptions;
    }

    public function scopeFindByUuidOrId(Builder $query, string $uuid): BaseModel
    {
        return $query->where(function ($q) use ($uuid) {
            $q->where('uuid', $uuid)
              ->orWhere('id', $uuid);
        })->firstOrFail();
    }
}
```

### 2.5 Relationship Patterns

**Many-to-Many with Pivot:**
```php
// In Vendor model
public function group(): BelongsToMany
{
    return $this->belongsToMany(VendorGroup::class, 'vendors_groups_pivot', 'vendor_id', 'vendor_group_id')
        ->withPivot(['id'])
        ->withTimestamps();
}

// In VendorGroup model
public function vendors(): BelongsToMany
{
    return $this->belongsToMany(Vendor::class, 'vendors_groups_pivot', 'vendor_group_id', 'vendor_id')
        ->withPivot(['id'])
        ->withTimestamps();
}
```

**BelongsTo:**
```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}

public function branch(): BelongsTo
{
    return $this->belongsTo(Branch::class, 'branch_id');
}
```

**Polymorphic:**
```php
// In Payment model
public function finance()
{
    return $this->morphTo('finance', 'finance_type', 'finance_id');
}
```

---

## 3. UI/VIEW PATTERNS

### 3.1 Filter Sidebar Implementation

**Component:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/VendorGroup/FilterSidebarComponent.php`

```php
<?php

namespace Polirium\Modules\Vendor\Http\Livewire\VendorGroup;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch("datatable-vendor-group-filter", $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['name' => ''];
        $this->dispatch("datatable-vendor-group-filter", '', 'name');
    }

    public function render()
    {
        return view('modules/vendor::vendor-group.filter-sidebar');
    }
}
```

**View:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/vendor-group/filter-sidebar.blade.php`

```blade
<div>
    {{-- Filter Panel --}}
    <x-ui::card>
        {{-- Header with icon --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                {!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}
                <span class="fw-semibold">{{ __('core/base::general.filter') }}</span>
            </div>
        </div>

        {{-- Search input --}}
        <div class="mb-3">
            <label class="form-label small text-muted">
                {{ __('core/base::general.search_by_name') }}
            </label>
            <div class="input-icon">
                <span class="input-icon-addon">
                    {!! tabler_icon('search', ['class' => 'icon']) !!}
                </span>
                <input
                    type="text"
                    class="form-control"
                    wire:model.live.debounce.300ms="search.name"
                    placeholder="{{ __('core/base::general.search_placeholder') }}"
                >
            </div>
        </div>

        {{-- Active filter indicator --}}
        @if (!empty($search['name']))
            <div class="p-2 bg-primary-lt rounded d-flex align-items-center justify-content-between">
                <span class="small text-primary">
                    {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                    {{ __('core/base::general.filter_active') }}
                </span>
                <button
                    class="btn btn-sm btn-ghost-danger btn-icon"
                    wire:click="clearFilter"
                    title="{{ __('core/base::general.clear_filter') }}"
                >
                    {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                </button>
            </div>
        @endif
    </x-ui::card>

    {{-- Quick Actions Card --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            {!! tabler_icon('bolt', ['class' => 'icon text-warning']) !!}
            <span class="fw-semibold">{{ __('core/base::general.quick_actions') }}</span>
        </div>

        <div class="d-grid gap-2">
            <button
                class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2"
                wire:click="$dispatch('show-modal-create-vendor-group')"
            >
                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                {{ __('modules/vendor::vendor.group.create') }}
            </button>
        </div>
    </x-ui::card>
</div>
```

### 3.2 DataTable Pattern (PowerGrid)

**Component:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/VendorGroup/Datatable/VendorGroupTable.php`

```php
<?php

namespace Polirium\Modules\Vendor\Http\Livewire\VendorGroup\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\PowerGridFields;

final class VendorGroupTable extends BaseTable
{
    public string $tableName = 'table-vendor-groups';

    public array $request = [];

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('modules/vendor::vendor-group.datatable.header-actions'),
            
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount()
                ->includeViewOnBottom('modules/vendor::vendor-group.datatable.footer'),
        ];
    }

    public function datasource(): Builder
    {
        return VendorGroup::query()
            ->orderByDesc("id");
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields();
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')
                ->sortable()
                ->searchable(),
            
            Column::make(trans('modules/vendor::vendor.group.name'), 'name')
                ->sortable()
                ->searchable(),
            
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(VendorGroup $row): array
    {
        return [
            Button::add('edit-modal-create-vendor-group')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->dispatch('show-modal-create-vendor-group', ['id' => $row->id]),
        ];
    }
}
```

**Base Table:** `/Users/vingamagic/Developer/php/polirium/platform/core/support/src/Http/Livewire/Tables/BaseTable.php`

```php
<?php

namespace Polirium\Core\Support\Http\Livewire\Tables;

use Polirium\Datatable\PowerGridComponent;
use Polirium\Datatable\Traits\WithExport;

class BaseTable extends PowerGridComponent
{
    use WithExport;

    public function customThemeClass(): ?string
    {
        return \Polirium\Core\UI\Theme\PoliPowerGrid::class;
    }
}
```

### 3.3 Index Page Layout

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/vendor-group/index.blade.php`

```blade
<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ trans('modules/vendor::vendor.group.index') }}
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <button class="btn btn-primary d-none d-sm-inline-block"
                                onclick="Livewire.dispatch('show-modal-create-vendor-group', [])">
                            {!! tabler_icon('plus', ['class' => 'icon']) !!}
                            {{ trans('modules/vendor::vendor.group.create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            @livewire('modules/vendor::vendor-group.filter-sidebar')
        </div>
        <div class="col-md-9">
            <x-ui::card>
                @livewire('modules/vendor::vendor-group-table')
            </x-ui::card>
        </div>
    </div>

    @livewire('modules/vendor::index.modal.modal-create-vendor-group')
</x-ui.layouts::app>
```

### 3.4 Modal Pattern

**Component:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/Index/Modal/ModalCreateVendorGroupComponent.php`

```php
<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Index\Modal;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;

class ModalCreateVendorGroupComponent extends Component
{
    public ?int $vendor_group_id = null;

    public array $input = [
        'name' => '',
        'user_created_id' => null,
        'note' => null,
    ];

    protected function rules()
    {
        $table = (new VendorGroup)->getTable();

        return [
            'input.name' => "required|string|max:191|unique:{$table},name,{$this->vendor_group_id},id",
            'input.user_created_id' => 'required|numeric|integer',
            'input.note' => 'nullable|string|max:191',
        ];
    }

    public function mount()
    {
        $this->resetInput();
    }

    public function render()
    {
        return view('modules/vendor::index.modal.modal-create-vendor-group');
    }

    public function resetInput()
    {
        $this->input = [
            'name' => '',
            'user_created_id' => auth()->id(),
            'note' => null,
        ];
    }

    #[On('show-modal-create-vendor-group')]
    public function showModal(?int $id = null)
    {
        $this->vendor_group_id = $id;
        $this->resetInput();

        if ($id) {
            $groupModel = VendorGroup::findOrFail($id);
            $this->input = $groupModel->toArray();
        }

        $this->dispatch("modal", "modal-create-vendor-group");
    }

    public function save()
    {
        $this->validate();

        if ($this->vendor_group_id) {
            $group = VendorGroup::find($this->vendor_group_id);
            $group->update($this->input);
        } else {
            VendorGroup::create($this->input);
        }

        $this->resetInput();

        // Close modal
        $this->dispatch("close-modal-vendor-group");

        // Refresh tables
        $this->dispatch('pg:eventRefresh-table-vendors');
        $this->dispatch('pg:eventRefresh-table-vendor-groups');
    }
}
```

**View:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/index/modal/modal-create-vendor-group.blade.php`

```blade
<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-vendor-group" 
                    :header="trans('modules/vendor::vendor.group.' . ($vendor_group_id ? 'edit' : 'create'))">
            <x-ui::errors/>

            <div class="row g-3">
                <div class="col-12">
                    <x-ui.form.input
                        wire:model="input.name"
                        :label="trans('modules/vendor::vendor.group.name')"
                        :placeholder="__('modules/vendor::vendor.group.enter_name')"
                        icon="users-group"
                        required
                    />
                </div>

                <div class="col-12">
                    <x-ui.form.textarea
                        wire:model="input.note"
                        :label="trans('core/base::general.note')"
                        :placeholder="trans('core/base::general.enter_note')"
                        rows="2"
                    />
                </div>
            </div>

            <x-slot:footer>
                <x-ui::button color="secondary" :ghost="true" type="button" 
                             data-bs-dismiss="modal" icon="x">
                    {{ trans('core/base::general.cancel') }}
                </x-ui::button>
                <x-ui::button color="primary" type="submit" icon="device-floppy" 
                             wire:loading.attr="disabled">
                    {{ trans('core/base::general.save') }}
                </x-ui::button>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

@push('scripts')
<script>
    window.addEventListener('close-modal-vendor-group', event => {
        const modalEl = document.getElementById('modal-create-vendor-group');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            } else {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                modalEl.removeAttribute('role');
            }
        }

        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 150);
    });
</script>
@endpush
```

### 3.5 Icon Usage (tabler_icon)

**Helper Location:** `/Users/vingamagic/Developer/php/polirium/platform/packages/laravel-tabler-icons/helpers/icon.php`

```php
<?php

if (! function_exists('tabler_icon')) {
    function tabler_icon(string $name, array $attributes = [])
    {
        return app('tabler-icon')::render($name, $attributes);
    }
}
```

**Usage Examples:**
```blade
{{-- Icon with class --}}
{!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}

{{-- Icon button --}}
<button>
    {!! tabler_icon('pencil', ['class' => 'icon']) !!}
</button>

{{{-- In x-ui::button component --}}
<x-ui::button icon="plus">{{ __('Add New') }}</x-ui::button>
```

**Common Icons:**
| Icon Name | Usage |
|-----------|-------|
| `plus` | Add/Create |
| `pencil` | Edit |
| `trash` | Delete |
| `check` | Active/Success |
| `player-pause` | Inactive/Pause |
| `settings` | Settings |
| `filter` | Filter |
| `search` | Search |
| `x` | Close/Cancel |
| `device-floppy` | Save |

---

## 4. PERMISSION & MENU SYSTEM

### 4.1 Permission Configuration

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/permissions.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Nhà cung cấp
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Nhà cung cấp',
        'flag' => 'vendors',
    ],
    [
        'name' => 'Xem danh sách nhà cung cấp',
        'flag' => 'vendors.index',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem danh sách nhóm nhà cung cấp',
        'flag' => 'vendors.groups',
        'parent_flag' => 'vendors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nhập hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Xem danh sách nhập hàng',
        'flag' => 'vendors.purchases.index',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Tạo phiếu nhập',
        'flag' => 'vendors.purchases.create',
        'parent_flag' => 'vendors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trả hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Trả hàng nhà cung cấp',
        'flag' => 'vendors.refunds.index',
        'parent_flag' => 'vendors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chuyển hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Chuyển hàng',
        'flag' => 'vendors.transfers.index',
        'parent_flag' => 'vendors',
    ],
];
```

**Permission Structure:**
```php
[
    'name' => 'Display Name',           // Human-readable name
    'flag' => 'permission.flag',        // Permission flag (dot notation)
    'parent_flag' => 'parent.flag',     // Parent permission (optional)
]
```

### 4.2 Menu Configuration

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/menu.php`

```php
<?php

return [
    [
        'id' => 'module_vendor_index',
        'name' => trans('modules/vendor::vendor.name'),
        'route' => 'vendors.index',
        'parent' => 'module_customer',
        'icon' => 'truck-delivery',
        'sort' => 0,
        'permission' => 'vendors.view',
    ],

    [
        'id' => 'module_vendor_group',
        'name' => trans('modules/vendor::vendor.group.name'),
        'route' => 'vendors.group',
        'parent' => 'module_customer',
        'icon' => 'user',
        'sort' => 3,
        'permission' => 'vendors.groups',
    ],

    [
        'id' => 'module_vendor_trade',
        'name' => trans('modules/vendor::transfer.name'),
        'route' => null,
        'icon' => 'users',
        'sort' => 1,
    ],
    [
        'id' => 'module_vendor_purchase',
        'name' => trans('modules/vendor::purchase.name'),
        'route' => 'vendors.purchases.index',
        'parent' => 'module_vendor_trade',
        'icon' => 'user',
        'sort' => 0,
        'permission' => 'purchases.view',
    ],
];
```

**Menu Structure:**
```php
[
    'id' => 'unique_menu_id',           // Unique identifier
    'name' => 'Display Name',           // Use trans() for translations
    'route' => 'route.name',            // Route name (null if parent only)
    'parent' => 'parent_menu_id',       // Parent menu ID (null if root)
    'icon' => 'tabler-icon-name',       // Tabler icon name
    'sort' => 0,                        // Sort order
    'permission' => 'permission.flag',  // Required permission
]
```

### 4.3 Route Protection

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\Vendor\Http\Controllers')
    ->group(function () {
        Route::prefix('vendors')->name('vendors.')->group(function () {
            Route::get('', 'VendorController@index')
                ->name('index')
                ->middleware('can:vendors.index');
            
            Route::get('group', 'VendorController@group')
                ->name('group')
                ->middleware('can:vendors.groups');

            Route::prefix('purchases')->name('purchases.')->group(function () {
                Route::get('', 'PurchaseController@index')
                    ->name('index')
                    ->middleware('can:vendors.purchases.index');
                
                Route::get('order/{id?}', 'PurchaseController@order')
                    ->name('order')
                    ->middleware('can:vendors.purchases.create');
            });
        });
    });
```

**Route Pattern:**
```php
Route::prefix(admin_prefix())          // e.g., /admin
    ->middleware(['web', 'auth'])       // Auth middleware
    ->namespace('Polirium\Modules\[Module]\Http\Controllers')
    ->group(function () {
        Route::get('', 'Controller@action')
            ->name('route.name')
            ->middleware('can:permission.flag');
    });
```

---

## 5. LIVEREGISTRATION

### 5.1 Livewire Config

**File:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/livewire.php`

```php
<?php

use Polirium\Modules\Vendor\Http\Livewire\Index\Modal\ModalCreateVendorComponent;
use Polirium\Modules\Vendor\Http\Livewire\Index\Datatable\VendorTable;
use Polirium\Modules\Vendor\Http\Livewire\Index\Modal\ModalCreateVendorGroupComponent;
use Polirium\Modules\Vendor\Http\Livewire\Index\SearchSidebarComponent;
use Polirium\Modules\Vendor\Http\Livewire\VendorGroup\Datatable\VendorGroupTable;
use Polirium\Modules\Vendor\Http\Livewire\VendorGroup\FilterSidebarComponent;

return [
    'search-sidebar-vendor' => [
        'class' => SearchSidebarComponent::class,
        'alias' => 'modules/vendor::index.search-sidebar',
        'description' => 'Vendor search sidebar',
    ],
    'vendor-table' => [
        'class' => VendorTable::class,
        'alias' => 'modules/vendor::vendor-table',
        'description' => 'Vendor Table',
    ],
    'modal-create-vendor' => [
        'class' => ModalCreateVendorComponent::class,
        'alias' => 'modules/vendor::index.modal.modal-create-vendor',
        'description' => 'Modal create vendor',
    ],
    'modal-create-vendor-group' => [
        'class' => ModalCreateVendorGroupComponent::class,
        'alias' => 'modules/vendor::index.modal.modal-create-vendor-group',
        'description' => 'Modal create vendor group',
    ],
    'vendor-group-table' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\VendorGroup\Datatable\VendorGroupTable::class,
        'alias' => 'modules/vendor::vendor-group-table',
        'description' => 'Vendor Group Table',
    ],
    'vendor-group-filter-sidebar' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\VendorGroup\FilterSidebarComponent::class,
        'alias' => 'modules/vendor::vendor-group.filter-sidebar',
        'description' => 'Vendor Group Filter Sidebar',
    ],
];
```

**Usage in Views:**
```blade
@livewire('modules/vendor::vendor-group.filter-sidebar')
@livewire('modules/vendor::vendor-group-table')
@livewire('modules/vendor::index.modal.modal-create-vendor-group')
```

---

## 6. TRANSLATION PATTERNS

### 6.1 Translation File Structure

**Location:** `resources/lang/{locale}/{module}.php`

**Example:** `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/lang/vi/vendor.php`

```php
<?php

return [
    'name' => 'Nhà cung cấp',
    'create' => 'Thêm nhà cung cấp',
    'edit' => 'Sửa nhà cung cấp',
    'vat' => 'MST',
    'company' => 'Công ty',
    'code' => 'Mã NCC',
    'enter_code' => 'Nhập mã nhà cung cấp...',
    'enter_name' => 'Nhập tên nhà cung cấp...',
    'enter_company' => 'Nhập tên công ty...',
    'enter_vat' => 'Nhập mã số thuế...',
    'select_groups' => 'Chọn nhóm nhà cung cấp...',
    'basic_info' => 'Thông tin cơ bản',
    'contact_location' => 'Liên hệ & Địa chỉ',
    'branch' => 'Chi nhánh',

    'import' => [
        'title' => 'Nhập sản phẩm từ file Excel',
        'download_template' => 'Tải về file mẫu:',
        'excel_file' => 'Excel file',
        'error_details' => 'Lỗi chi tiết:',
        'more_errors' => '... và :count lỗi khác',
        'file_columns' => 'File Excel cần có các cột: Mã hàng, Tên hàng, Đơn vị tính, Đơn giá, Giảm giá, Giảm giá (%), Số lượng',
        'processing' => 'Đang xử lý...',
        'import_products' => 'Import sản phẩm',
        'refund_title' => 'Nhập sản phẩm trả hàng từ file Excel',
        'refund_file_columns' => 'File Excel cần có các cột: Mã hàng, Tên hàng, Đơn vị tính, Số lượng, Giá nhập, Giá trả lại',
    ],

    'group' => [
        'name' => 'Nhóm NCC',
        'create' => 'Thêm Nhóm NCC',
        'edit' => 'Sửa Nhóm NCC',
        'enter_name' => 'Nhập tên nhóm NCC...',
    ],
];
```

### 6.2 Translation Usage

**In Blade:**
```blade
{{ trans('modules/vendor::vendor.name') }}
{{ __('modules/vendor::vendor.create') }}
{{ __('modules/vendor::vendor.group.name') }}
```

**In PHP:**
```php
trans('modules/vendor::vendor.name')
__('modules/vendor::vendor.create')
```

**Translation Key Format:**
```
modules/{module}::{file}.{key}
modules/{module}::{file}.{nested}.{key}
```

---

## 7. CSS & ASSETS

### 7.1 CSS Build Process

**IMPORTANT:** All CSS must be in `platform/core/ui/resources/assets/scss/polirium/` and imported into `app.scss`

**Build Commands (from ROOT):**
```bash
cd /Users/vingamagic/Developer/php/polirium
npm run dev      # Development
npm run prod     # Production
```

**DO NOT:**
- Create CSS in module's `public/css/`
- Load CSS in Livewire controllers
- Run build from module directory

### 7.2 Module JavaScript (webpack.mix.js)

**Example:** `/Users/vingamagic/Developer/php/polirium/platform/modules/accounting/webpack.mix.js`

```javascript
let mix = require('laravel-mix');
let path = require('path');
let directory = path.basename(path.resolve(__dirname));

// Get the relative path from root to this directory
const rootPath = path.resolve(__dirname, '../../..');
const relativePath = path.relative(rootPath, __dirname);

// Path configuration
const source = relativePath;
const assets = source + '/resources/assets';
const publicPath = source + '/public';
const productFolder = 'public/vendor/polirium/modules/' + directory;

mix.disableNotifications();

// JS files
const jsFiles = [
    'accounting',
];

// Compile JS files
jsFiles.forEach(function (file) {
    mix.js(assets + '/js/' + file + '.js', productFolder + '/js/' + file + '.min.js');
});

// Copy built files back to public folder
mix.then(() => {
    const fs = require('fs');

    jsFiles.forEach(function (file) {
        const sourceFile = productFolder + '/js/' + file + '.min.js';
        const targetFile = publicPath + '/js/' + file + '.min.js';

        if (fs.existsSync(sourceFile)) {
            const targetDir = path.dirname(targetFile);
            if (!fs.existsSync(targetDir)) {
                fs.mkdirSync(targetDir, { recursive: true });
            }
            fs.copyFileSync(sourceFile, targetFile);
        }
    });
});
```

---

## 8. KEY PATTERNS FOR PROJECT TASK MANAGEMENT

### 8.1 Hierarchical Data Pattern (Projects → Tasks → Subtasks)

**Similar to:** Vendor → VendorGroup (many-to-many)

**Suggested Structure:**
```php
// Project model
public function tasks(): HasMany
{
    return $this->hasMany(Task::class)->orderBy('sort_order');
}

// Task model
public function project(): BelongsTo
{
    return $this->belongsTo(Project::class);
}

public function subtasks(): HasMany
{
    return $this->hasMany(Subtask::class)->orderBy('sort_order');
}

public function parent(): BelongsTo
{
    return $this->belongsTo(Task::class, 'parent_id');
}

public function children(): HasMany
{
    return $this->hasMany(Task::class, 'parent_id');
}
```

### 8.2 Date Fields Pattern

**For Start/End Dates:**
```php
// Migration
$table->date('start_date')->nullable();
$table->date('end_date')->nullable();
$table->date('due_date')->nullable();

// Model fillable
protected $fillable = [
    'start_date',
    'end_date',
    'due_date',
];
```

### 8.3 Status Field Pattern

**For Task Status:**
```php
// Migration
$table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'])
    ->default('pending');

// Model accessor
public function getStatusLabelAttribute()
{
    return match ($this->status) {
        'pending' => __('modules/tasks::status.pending'),
        'in_progress' => __('modules/tasks::status.in_progress'),
        'completed' => __('modules/tasks::status.completed'),
        'on_hold' => __('modules/tasks::status.on_hold'),
        'cancelled' => __('modules/tasks::status.cancelled'),
        default => $this->status,
    };
}
```

### 8.4 Priority Field Pattern

```php
// Migration
$table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

// Model
public function getPriorityLabelAttribute()
{
    return match ($this->priority) {
        'low' => __('modules/tasks::priority.low'),
        'medium' => __('modules/tasks::priority.medium'),
        'high' => __('modules/tasks::priority.high'),
        'urgent' => __('modules/tasks::priority.urgent'),
        default => $this->priority,
    };
}
```

### 8.5 User Assignment Pattern

```php
// Migration
$table->integer('assigned_to')->nullable()->comment('User assigned to task');
$table->integer('created_by')->nullable();
$table->integer('updated_by')->nullable();

// Model
public function assignedTo(): BelongsTo
{
    return $this->belongsTo(User::class, 'assigned_to');
}

public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updatedBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

---

## 9. QUICK START CHECKLIST

### Creating a New Module

- [ ] Create directory structure
- [ ] Create `composer.json` with PSR-4 namespace
- [ ] Create ServiceProvider extending `PoliriumBaseServiceProvider`
- [ ] Configure `loadConfigurations()`, `loadViews()`, `loadTranslations()`, `loadRoutes()`, `loadMigrations()`
- [ ] Create `config/livewire.php` for component registration
- [ ] Create `config/permissions.php` with permission flags
- [ ] Create `config/menu.php` with menu items
- [ ] Create migrations with proper column patterns
- [ ] Create models extending `BaseModel`
- [ ] Create Livewire components (FilterSidebar, DataTable, Modal)
- [ ] Create blade views (index, filter-sidebar, modal)
- [ ] Create translation files (`resources/lang/vi/`, `resources/lang/en/`)
- [ ] Create routes with permission middleware
- [ ] Test module functionality

---

## 10. RELEVANT FILE PATHS

### Vendor Module (Reference)
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/composer.json`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Providers/VendorServiceProvider.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/permissions.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/menu.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/livewire.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/routes/web.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/database/migrations/2024_11_06_154144_vendor_create_vendor_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Model/Vendor.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Model/VendorGroup.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/VendorGroup/FilterSidebarComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/VendorGroup/Datatable/VendorGroupTable.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/Index/Modal/ModalCreateVendorGroupComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/vendor-group/index.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/vendor-group/filter-sidebar.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/index/modal/modal-create-vendor-group.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/lang/vi/vendor.php`

### Core Files
- `/Users/vingamagic/Developer/php/polirium/platform/core/base/src/Http/Models/BaseModel.php`
- `/Users/vingamagic/Developer/php/polirium/platform/core/support/src/Providers/PoliriumBaseServiceProvider.php`
- `/Users/vingamagic/Developer/php/polirium/platform/core/support/src/Traits/LoadAndPublishDataTrait.php`
- `/Users/vingamagic/Developer/php/polirium/platform/core/support/src/Http/Livewire/Tables/BaseTable.php`
- `/Users/vingamagic/Developer/php/polirium/platform/packages/laravel-tabler-icons/helpers/icon.php`

### Documentation
- `/Users/vingamagic/Developer/php/polirium/platform/docs/03-creating-modules.md`
- `/Users/vingamagic/Developer/php/polirium/README.md`
- `/Users/vingamagic/Developer/php/polirium/CLAUDE.md`

---

**END OF REPORT**
