# Settings System

Hệ thống Settings cho phép modules đăng ký cài đặt riêng biệt một cách dễ dàng thông qua `SettingRegistry`.

---

## Tổng quan

Settings System nằm trong `platform/core/settings` với các thành phần:

- **SettingRegistry** - Quản lý registration của setting groups và fields
- **DynamicSettings** - Generic Livewire component render settings từ registry
- **Settings Facade** - Lưu/lấy settings từ database
- **Tab UI** - Giao diện với sidebar navigation cho mỗi group

---

## Cấu trúc Settings

### Settings Group

Một settings group là một tập hợp các cài đặt liên quan, được hiển thị trong một tab riêng biệt:

```php
SettingRegistry::group('group_key', [
    'title' => 'Display Title',
    'icon' => 'tabler-icon-name',
    'description' => 'Group description (optional)'
])
```

### Setting Fields

Các field types được hỗ trợ:

| Type | Description | Example |
|------|-------------|---------|
| `text` | Text input | Site name, email |
| `password` | Password input | SMTP password, API key |
| `number` | Number input | Port, limit |
| `email` | Email input | SMTP email |
| `url` | URL input | API endpoint |
| `textarea` | Multi-line text | Description, signature |
| `select` | Dropdown selection | Choose option from list |
| `checkbox` | Boolean toggle | Enable/disable feature |
| `file` | File upload | Logo, favicon |

---

## Đăng ký Settings cho Module

### Cách 1: Trong ServiceProvider (Khuyên dùng)

Tạo hoặc sửa file ServiceProvider của module:

```php
<?php

namespace Polirium\Modules\YourModule\Providers;

use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;
use Polirium\Core\Settings\Facades\SettingRegistry;

class YourModuleServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this->setNamespace('modules/your-module')
            ->loadConfigurations(['settings'])
            ->loadViews()
            ->loadRoutes(['web']);

        // Register settings
        $this->registerSettings();
    }

    protected function registerSettings(): void
    {
        SettingRegistry::group('your_module', [
            'title' => 'Your Module Settings',
            'icon' => 'settings',
            'description' => 'Configure your module features',
        ])
        ->add('api_key', [
            'type' => 'text',
            'label' => 'API Key',
            'description' => 'Enter your API key',
            'required' => true,
            'validation' => ['required', 'string', 'max:255'],
        ])
        ->add('timeout', [
            'type' => 'number',
            'label' => 'Request Timeout (seconds)',
            'default' => 30,
            'validation' => ['required', 'integer', 'min:1', 'max:300'],
        ])
        ->add('enable_logging', [
            'type' => 'checkbox',
            'label' => 'Enable Logging',
            'default' => false,
        ])
        ->add('logo', [
            'type' => 'file',
            'label' => 'Module Logo',
            'description' => 'Upload logo for this module',
            'validation' => ['nullable', 'image', 'max:1024'],
            'attributes' => ['accept' => 'image/*'],
        ]);
    }
}
```

### Cách 2: Via Config File (Advanced)

Tạo config file `config/modules/your-module/settings.php`:

```php
<?php

use Polirium\Core\Settings\Facades\SettingRegistry;

return [
    'groups' => [
        'your_module' => [
            'title' => 'Your Module Settings',
            'icon' => 'settings',
            'description' => 'Configure your module',
            'settings' => [
                'api_key' => [
                    'type' => 'text',
                    'label' => 'API Key',
                    'required' => true,
                    'validation' => ['required', 'string'],
                ],
                // ... more settings
            ],
        ],
    ],
];
```

Sau đó load config trong ServiceProvider:

```php
$this->registerSettingsFromConfig(config('modules.your-module.settings.groups'));
```

---

## Sử dụng Settings trong Code

### Via Facade

```php
use Polirium\Core\Settings\Facades\Settings;

// Get value
$apiKey = Settings::get('your_module.api_key', 'default_value');

// Set value
Settings::set('your_module.api_key', 'new_value');

// Check if exists
if (Settings::has('your_module.api_key')) {
    // ...
}

// Boolean helper
if (Settings::isTrue('your_module.enable_logging')) {
    // Logging is enabled
}
```

### Via Helper Function

```php
// Get value
$value = settings('your_module.api_key', 'default');

// Set value
settings(['your_module.api_key' => 'new_value']);

// Get settings instance
$settings = settings();
$settings->get('your_module.api_key');
```

---

## Ví dụ Thực tế

### Ví dụ 1: Email Settings

Email settings đã được đăng ký sẵn trong `BaseServiceProvider` của module `core/base`.

```php
// In BaseServiceProvider::registerSettings()
SettingRegistry::group('email', [
    'title' => 'Email Configuration',
    'icon' => 'mail',
    'description' => 'Configure SMTP settings for sending emails',
])
->add('smtp_host', [
    'type' => 'text',
    'label' => 'SMTP Host',
    'description' => 'e.g., smtp.gmail.com, smtp.office365.com',
    'default' => config('mail.host', 'smtp.mailgun.org'),
    'required' => true,
    'validation' => ['required', 'string', 'max:255'],
])
->add('smtp_port', [
    'type' => 'number',
    'label' => 'SMTP Port',
    'description' => 'Common ports: 25, 465 (SSL), 587 (TLS)',
    'default' => config('mail.port', 587),
    'required' => true,
    'validation' => ['required', 'integer', 'min:1', 'max:65535'],
    'attributes' => ['min' => 1, 'max' => 65535],
])
->add('smtp_username', [
    'type' => 'text',
    'label' => 'SMTP Username',
    'description' => 'Email address or username for SMTP authentication',
    'default' => config('mail.username'),
    'validation' => ['nullable', 'string', 'max:255'],
])
->add('smtp_password', [
    'type' => 'password',
    'label' => 'SMTP Password',
    'description' => 'Password for SMTP authentication',
    'validation' => ['nullable', 'string', 'max:255'],
])
->add('smtp_encryption', [
    'type' => 'select',
    'label' => 'Encryption',
    'description' => 'Choose the encryption protocol',
    'options' => [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        '' => 'None',
    ],
    'default' => config('mail.encryption', 'tls'),
    'validation' => ['nullable', 'string', 'in:tls,ssl,'],
])
->add('from_email', [
    'type' => 'email',
    'label' => 'From Email',
    'description' => 'Email address that will appear as sender',
    'default' => config('mail.from.address'),
    'required' => true,
    'validation' => ['required', 'email', 'max:255'],
])
->add('from_name', [
    'type' => 'text',
    'label' => 'From Name',
    'description' => 'Name that will appear as sender',
    'default' => config('mail.from.name', config('app.name')),
    'required' => true,
    'validation' => ['required', 'string', 'max:255'],
])
->add('email_signature', [
    'type' => 'textarea',
    'label' => 'Email Signature',
    'description' => 'This signature will be appended to all outgoing emails',
    'validation' => ['nullable', 'string', 'max:1000'],
    'attributes' => ['rows' => 4],
]);
```

### Ví dụ 2: Integration Settings

```php
SettingRegistry::group('integrations', [
    'title' => 'Third-Party Integrations',
    'icon' => 'brand-google',
    'description' => 'Connect with external services',
])
->add('google_analytics_id', [
    'type' => 'text',
    'label' => 'Google Analytics ID',
    'description' => 'Example: UA-123456789-1 or G-XXXXXXXXXX',
    'validation' => ['nullable', 'string', 'max:50'],
])
->add('google_maps_key', [
    'type' => 'text',
    'label' => 'Google Maps API Key',
    'validation' => ['nullable', 'string'],
])
->add('recaptcha_site_key', [
    'type' => 'text',
    'label' => 'reCAPTCHA Site Key',
    'validation' => ['nullable', 'string'],
])
->add('recaptcha_secret_key', [
    'type' => 'text',
    'label' => 'reCAPTCHA Secret Key',
    'validation' => ['nullable', 'string'],
]);
```

### Ví dụ 3: Feature Toggles

```php
SettingRegistry::group('features', [
    'title' => 'Feature Flags',
    'icon' => 'toggle-left',
    'description' => 'Enable or disable application features',
])
->add('maintenance_mode', [
    'type' => 'checkbox',
    'label' => 'Maintenance Mode',
    'description' => 'When enabled, only administrators can access the site',
    'default' => false,
])
->add('enable_registration', [
    'type' => 'checkbox',
    'label' => 'Allow User Registration',
    'default' => true,
])
->add('enable_notifications', [
    'type' => 'checkbox',
    'label' => 'Enable Email Notifications',
    'default' => true,
])
->add('max_upload_size', [
    'type' => 'number',
    'label' => 'Max Upload Size (MB)',
    'default' => 10,
    'validation' => ['required', 'integer', 'min:1', 'max:100'],
]);
```

---

## Setting Field Configuration

### Full Configuration Options

```php
->add('field_key', [
    // Basic
    'type' => 'text',           // Field type (text, password, number, email, url, textarea, select, checkbox, file)
    'label' => 'Field Label',    // Display label
    'description' => 'Help text shown below field',
    'default' => 'default_value',

    // Validation
    'required' => true,         // Whether field is required
    'validation' => ['required', 'string', 'max:255'], // Laravel validation rules

    // Options (for select type)
    'options' => [
        'value1' => 'Label 1',
        'value2' => 'Label 2',
    ],

    // HTML Attributes
    'attributes' => [
        'placeholder' => 'Enter value...',
        'accept' => 'image/*',    // For file inputs
        'rows' => 5,              // For textarea
        'min' => 1,               // For number inputs
        'max' => 100,             // For number inputs
        'step' => 0.01,           // For number inputs
    ],
])
```

---

## UI Structure

### Desktop Layout

```
+------------------+--------------------------+
|  Sidebar (25%)   |   Content (75%)           |
|                  |                           |
|  [General]       |   +-------------------+   |
|  [Email]         |   | Group Header     |   |
|  [Integrations]  |   | Title + Icon      |   |
|  [Features]      |   | Description      |   |
|                  |   +-------------------+   |
|  +---+            |                           |
|  |   |            |   +-------------------+   |
|  +---+            |   | Settings Form    |   |
|                  |   |                   |   |
|                  |   | Field 1           |   |
|                  |   | Field 2           |   |
|                  |   | Field 3           |   |
|                  |   |                   |   |
|                  |   | [Save] [Cancel]   |   |
|                  |   +-------------------+   |
+------------------+--------------------------+
```

### Mobile Layout

```
+----------------------------+
|  Settings                  |
+----------------------------+
|  [Select Group ▼]          |
+----------------------------+
|  +------------------------+|
|  | Group Header           ||
|  +------------------------+|
|  +------------------------+|
|  | Settings Form          ||
|  |                        ||
|  | Field 1                ||
|  | Field 2                ||
|  | [Save] [Cancel]        ||
|  +------------------------+|
+----------------------------+
```

---

## Database Schema

Settings được lưu trong bảng `settings`:

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('team_id')->nullable();
    $table->string('key')->index();
    $table->longText('value')->nullable();
    $table->unique(['key', 'team_id']);
});
```

Key format: `{group_key}.{field_key}`

Ví dụ:
- `general.title`
- `general.logo`
- `email.smtp_host`
- `features.maintenance_mode`

---

## Tips & Best Practices

### 1. Group Naming Convention

Sử dụng snake_case với prefix module:

```php
// Good
SettingRegistry::group('accounting', [...])
SettingRegistry::group('product_catalog', [...])

// Avoid
SettingRegistry::group('AccountingSettings', [...])
SettingRegistry::group('module-1', [...])
```

### 2. Setting Keys

Đặt keys ngắn gọn, mô tả:

```php
// Good
->add('api_key', [...])
->add('timeout', [...])
->add('enable_cache', [...])

// Avoid
->add('the_api_key_for_this_module', [...])
->add('setting_1', [...])
```

### 3. Default Values

Luôn cung cấp default values hợp lý:

```php
->add('timeout', [
    'type' => 'number',
    'default' => 30, // Reasonable default
    // ...
])
```

### 4. Validation

Sử dụng Laravel validation rules đầy đủ:

```php
->add('email', [
    'type' => 'email',
    'required' => true,
    'validation' => ['required', 'email', 'max:255'],
    // Not just: ['required']
])
```

### 5. File Uploads

- Chỉnh max size phù hợp
- Sử dụng `accept` attribute
- Validate image types

```php
->add('logo', [
    'type' => 'file',
    'validation' => ['nullable', 'image', 'max:2048'], // Max 2MB
    'attributes' => ['accept' => 'image/*'],
])
```

---

## Troubleshooting

### Settings not showing up

1. **Clear cache:** `php artisan config:clear`
2. **Check registration:** Verify ServiceProvider is registered
3. **Verify group key:** Must match in view and registration

### Validation not working

1. **Check rules array:** Must be array of strings
2. **Verify `required` flag:** Or use `required` in validation rules
3. **Review field type:** Some types have special validation

### File upload not working

1. **Check form enctype:** Livewire handles this
2. **Verify directory permissions:** `storage/app` must be writable
3. **Confirm max file size:** Check PHP `upload_max_filesize`

---

## API Reference

### SettingRegistry Facade

| Method | Parameters | Return | Description |
|--------|-----------|--------|-------------|
| `group()` | `string $name, array $config` | `self` | Start defining a settings group |
| `add()` | `string $key, array $config` | `self` | Add setting to current group |
| `getGroups()` | - | `array` | Get all registered groups |
| `getGroup()` | `string $name` | `array\|null` | Get specific group |
| `getGroupSettings()` | `string $group` | `array` | Get settings in group |
| `hasGroup()` | `string $name` | `bool` | Check if group exists |
| `removeGroup()` | `string $name` | `self` | Remove a group |
| `clear()` | - | `self` | Clear all groups |

### Settings Facade

| Method | Parameters | Return | Description |
|--------|-----------|--------|-------------|
| `get()` | `string $key, $default` | `mixed` | Get setting value |
| `set()` | `string $key, $value` | `void` | Set setting value |
| `has()` | `string $key` | `bool` | Check if setting exists |
| `forget()` | `string $key` | `void` | Delete setting |
| `all()` | - | `array` | Get all settings |
| `flush()` | - | `void` | Clear all settings |
| `isTrue()` | `string $key, $default` | `bool` | Check if setting is true |
| `isFalse()` | `string $key, $default` | `bool` | Check if setting is false |

---

## Examples

See full example: `/platform/core/settings/examples/ExampleModuleServiceProvider.php`
