# Coding standards

## Bắt buộc theo project

- Module logic nằm trong `platform/modules/{name}`  
- Namespace: `Polirium\Modules\{Module}\` / `Polirium\Core\{Package}\`  
- `declare(strict_types=1);` khi phù hợp  
- Controller mỏng → Service / Action / Livewire  
- Permission trong `config/permissions.php` + translation  
- UI: Tabler classes; icon `tabler_icon()`  
- Label: `trans('module::file.key')`

## Git

- Conventional commits: `feat`, `fix`, `refactor`, `chore`, `docs`  
- Core/packages là submodule — commit đúng chỗ  

## Livewire

- Đăng ký alias trong `config/livewire.php`  
- Modal: pattern `x-ui::modal` + event `modal`  

## Tài liệu nội bộ

- Rules AI: `.cursorrules`  
- Docs: `platform/docs/`  
- Skill agent: `.claude/skills/polirium`
