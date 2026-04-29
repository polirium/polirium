# UI Libraries Research - Project Management Components

**Date**: 2026-01-17
**Focus**: Kanban & Gantt Chart libraries for Laravel/Livewire/Alpine.js stack
**Max Lines**: 150

---

## 1. KANBAN LIBRARIES

### Top Recommendations

#### 1.1 Livewire 4 `wire:sort` Directive ⭐ RECOMMENDED
- **Native Livewire 4 support** (as of Jan 2026)
- Built-in drag-sortable functionality
- Handles reordering, animations, ghost elements
- Supports drag handles, element ignoring, cross-group dragging
- **Best for**: Kanban boards with columns
- **Integration**: Zero dependencies, native Livewire
- **Source**: [Laracasts](https://laracasts.com)

#### 1.2 Alpine.js Sort Plugin
- Official Alpine.js plugin
- Powered by SortableJS core
- Explicit support for Kanban boards, to-do lists, sortable tables
- Screencasts available by Caleb Porzio (Livewire/Alpine creator)
- **Best for**: Lightweight drag-drop with Alpine.js
- **Integration**: `@alpinejs/sort` package
- **Sources**: [Laravel News](https://laravel-news.com), [Alpine.js.dev](https://alpinejs.dev)

#### 1.3 SortableJS (Direct Integration)
- Framework-agnostic library
- Community examples with Livewire components
- GitHub repos available for reference
- **Best for**: Custom implementations, full control
- **Integration**: Direct JS initialization
- **Source**: [GitHub community examples](https://github.com)

#### 1.4 @artisanpack-ui/livewire-drag-and-drop (Dec 2025)
- Accessibility-first (WCAG 2.1 AA compliant)
- Full keyboard navigation, screen reader support
- Built for TALL stack (Tailwind, Alpine, Laravel, Livewire)
- **Best for**: Accessibility requirements
- **Integration**: NPM package
- **Source**: [NPM](https://npmjs.com/package/@artisanpack-ui/livewire-drag-and-drop)

### Integration Patterns

```javascript
// Alpine.js Sort Plugin example
<div x-data="{ tasks: [] }" x-sort>
  <template x-for="task in tasks">
    <div x-sort:item>{{ task.name }}</div>
  </template>
</div>

// Livewire 4 wire:sort example
<ul wire:sort="{{ $tasks }}">
  <li wire:sort:item="{{ $task->id }}">{{ $task->name }}</li>
</ul>
```

---

## 2. GANTT CHART LIBRARIES

### Top Options

#### 2.1 DHTMLX Gantt ⭐ RECOMMENDED
- **Comprehensive, pure JavaScript**
- Rich functionality, high performance
- Compatible with any frontend/backend (including Laravel)
- **Features**:
  - Various task types, scheduling techniques
  - Resource management
  - Flexible API, customization
- **Licensing**: Free Standard (GPLv2) + Commercial PRO
- **Best for**: Production apps, advanced features
- **Source**: [DHTMLX.com](https://dhtmlx.com)

#### 2.2 Frappe Gantt ⭐ FREE/OPEN SOURCE
- **Lightweight, simple, MIT-licensed**
- Framework-agnostic
- Easy Laravel integration
- **Features**: Basic timeline visualization
- **Licensing**: Open-source (MIT)
- **Best for**: Prototypes, simple timelines, budget constraints
- **Source**: Listed in [AnyChart comparison](https://anychart.com)

#### 2.3 Bryntum Gantt
- Enterprise-grade scheduling engine
- Polished editing experience
- PHP backend integration guides
- **Licensing**: Commercial only
- **Best for**: Enterprise projects, budget available
- **Source**: [Bryntum.com](https://bryntum.com)

#### 2.4 AnyChart Gantt (AnyGantt)
- Part of AnyChart JS suite
- Framework-agnostic
- **Features**: Task hierarchies, dependencies, milestones, baselines
- **Licensing**: Free for non-commercial, commercial licenses available
- **Best for**: Existing AnyChart users, export options needed
- **Source**: [AnyChart.com](https://anychart.com)

### Integration Pattern (Laravel)

```javascript
// DHTMLX Gantt initialization
gantt.init("gantt_container");
gantt.parse({{
    data: {{ $tasks->toJson() }},
    links: {{ $links->toJson() }}
}});

// Event handling for Livewire updates
gantt.attachEvent("onAfterTaskUpdate", function(id, item){
    @this.updateTask(id, item);
});
```

---

## 3. TABLER ICONS

### Overview
- **5900+ free SVG icons** (as of 2024)
- Open-source, actively maintained
- Highly customizable (size, color, stroke)
- **Already in use** in Polirium codebase

### Project Management Icons Available
- **Calendar**: `calendar`, `calendar-plus`, `calendar-event`
- **Tasks**: `checklist`, `checkbox`, `clipboard-list`
- **Projects**: `folder`, `briefcase`, `chart-gantt`
- **Time**: `clock`, `timeline`, `calendar-time`
- **Actions**: `trash`, `edit`, `plus`, `settings`
- **Status**: `circle-check`, `circle-x`, `alert-circle`

### Usage Pattern (Existing Codebase)
```blade
{{-- Already used in Polirium --}}
{!! tabler_icon('pencil', ['class' => 'icon']) !!}
<x-ui::button icon="plus">{{ __('Add') }}</x-ui::button>
```

### Icon Search
- Browse all icons: [tabler.io/icons](https://tabler.io/icons)
- GitHub: [github.com/tabler/tabler-icons](https://github.com/tabler/tabler-icons)
- NPM: [@tabler/icons](https://www.npmjs.com/package/@tabler/icons)

---

## 4. RECOMMENDATIONS FOR POLIRIUM

### Kanban Board
1. **Primary**: Livewire 4 `wire:sort` (native, zero dependencies)
2. **Fallback**: Alpine.js Sort Plugin (if need Alpine features)
3. **Accessibility**: @artisanpack-ui/livewire-drag-and-drop (if WCAG required)

### Gantt Chart
1. **Production**: DHTMLX Gantt (free Standard edition sufficient)
2. **MVP/Prototype**: Frappe Gantt (MIT, lightweight)
3. **Enterprise**: Bryntum Gantt (if budget available)

### Icons
- Continue using Tabler Icons (already integrated)
- Use `tabler_icon()` helper for Blade views
- Use `icon` prop in `x-ui::button` components

---

## 5. INTEGRATION CHECKLIST

### Kanban Implementation
- [ ] Verify Livewire 4 version (confirm `wire:sort` available)
- [ ] Install Alpine.js Sort Plugin: `npm install @alpinejs/sort`
- [ ] Test drag-drop between columns (cross-group)
- [ ] Implement Livewire backend methods for reordering
- [ ] Add keyboard navigation (accessibility)

### Gantt Implementation
- [ ] Choose library (DHTMLX recommended)
- [ ] Create API endpoint for task data
- [ ] Initialize Gantt in Livewire view
- [ ] Wire up event handlers (create/update/delete tasks)
- [ ] Test with real project data

### Icons
- [ ] Review available PM icons on tabler.io/icons
- [ ] Document icon naming conventions
- [ ] Update translation keys for PM features

---

## 6. UNRESOLVED QUESTIONS

1. **Livewire Version**: Does Polirium use Livewire 4? (required for `wire:sort`)
2. **Performance**: Gantt chart performance with large datasets (1000+ tasks)?
3. **Mobile**: Mobile responsiveness for drag-drop (touch events)?
4. **Licensing**: DHTMLX GPL compatibility with Polirium's commercial use?
5. **Data Sync**: Real-time sync strategy for Gantt updates (debounce, Livewire defer)?

---

## SOURCES

- Kanban/Livewire: [Laracasts](https://laracasts.com), [Laravel News](https://laravel-news.com), [Alpine.js.dev](https://alpinejs.dev)
- SortableJS integration: [GitHub community](https://github.com)
- Accessibility package: [NPM - @artisanpack-ui/livewire-drag-and-drop](https://npmjs.com)
- DHTMLX Gantt: [dhtmlx.com](https://dhtmlx.com)
- Bryntum Gantt: [bryntum.com](https://bryntum.com)
- AnyChart Gantt: [anychart.com](https://anychart.com)
- Tabler Icons: [tabler.io/icons](https://tabler.io/icons), [GitHub](https://github.com/tabler/tabler-icons), [NPM](https://www.npmjs.com/package/@tabler/icons)

---

**Report prepared by**: Research Agent
**Next steps**: Prototype Kanban with Livewire 4 wire:sort, test Gantt with sample data
