# Cấu trúc thư mục

## Platform Structure

```
platform/
├── core/                    # Core packages
│   ├── base/               # Base functionality
│   ├── ui/                 # UI components
│   ├── media/              # Media management
│   ├── settings/           # Settings management
│   ├── support/            # Support classes
│   └── composer.json       # Core dependencies
├── modules/                # Business modules (custom)
├── packages/               # Third-party packages
└── docs/                   # Documentation
```

## Module/Package Structure

Mỗi module/package tuân theo cấu trúc chuẩn:

```
module-name/
├── config/
│   ├── menu.php           # Menu configuration
│   ├── permissions.php    # Permission flags
│   └── livewire.php       # Livewire components
├── database/
│   └── migrations/        # Database migrations
├── helpers/
│   └── *.php              # Helper functions
├── resources/
│   ├── views/             # Blade views
│   └── lang/              # Translations
├── routes/
│   ├── web.php            # Web routes
│   └── api.php            # API routes (optional)
├── src/
│   ├── Commands/          # Artisan commands
│   ├── Events/            # Event classes
│   ├── Facade/            # Facades
│   ├── Helpers/           # Helper classes
│   ├── Http/
│   │   ├── Controllers/   # HTTP controllers
│   │   ├── Livewire/      # Livewire components
│   │   ├── Models/        # Eloquent models
│   │   └── Requests/      # Form requests
│   ├── Providers/         # Service providers
│   ├── Service/           # Business services
│   └── Traits/            # Reusable traits
└── composer.json          # Module metadata
```

## Quan trọng: composer.json

Module cần khai báo provider trong `composer.json`:

```json
{
    "name": "polirium/module-name",
    "extra": {
        "laravel": {
            "providers": [
                "Polirium\\Modules\\ModuleName\\Providers\\ModuleServiceProvider"
            ]
        }
    },
    "autoload": {
        "psr-4": {
            "Polirium\\Modules\\ModuleName\\": "src/"
        }
    }
}
```
