# Project & Task Management Features Research

**Date:** 2025-01-17
**Researcher:** Claude Code
**Focus:** Data models, view types, progress management for PM module

---

## 1. Data Model Best Practices

### 1.1 Hierarchical Structure

**Multi-level Hierarchy** (Projects > Tasks > Subtasks)
- Support unlimited nesting depth for complex work breakdown
- Prefer breadth over excessive depth initially to avoid user confusion
- Use Hierarchical Task Analysis (HTA) for systematic breakdown

**Schema Pattern: Single Table with `parent_id`**
```sql
CREATE TABLE tasks (
  id BIGINT PRIMARY KEY,
  parent_id BIGINT NULL,  -- NULL = top-level project/task
  project_id BIGINT,       -- FK to projects table
  name VARCHAR(255),
  description TEXT,
  priority ENUM('low', 'medium', 'high', 'urgent'),
  status ENUM('todo', 'in_progress', 'review', 'done'),
  assigned_to BIGINT,      -- FK to users

  -- Date fields
  planned_start_date DATETIME,
  planned_end_date DATETIME,
  actual_start_date DATETIME,
  actual_end_date DATETIME,

  -- Progress tracking
  progress_percentage DECIMAL(5,2) DEFAULT 0,
  estimated_hours DECIMAL(10,2),
  actual_hours DECIMAL(10,2),

  -- Metadata
  created_at TIMESTAMP,
  updated_at TIMESTAMP,

  FOREIGN KEY (parent_id) REFERENCES tasks(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id)
);
```

**Key Design Decisions:**
- **Single table** (not separate Tasks/Subtasks) for flexibility and simpler queries
- **Recursive FK** (`parent_id → tasks.id`) enables unlimited nesting
- **NULL parent_id** = top-level task or project
- Separate `projects` table for project-level metadata (client, budget, etc.)

### 1.2 Dependency Management

**TaskRelationships Table** (for complex dependencies):
```sql
CREATE TABLE task_dependencies (
  id BIGINT PRIMARY KEY,
  predecessor_id BIGINT,   -- Task that must complete first
  successor_id BIGINT,     -- Task that depends on predecessor
  dependency_type ENUM('finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'),
  lag_days INT DEFAULT 0,

  FOREIGN KEY (predecessor_id) REFERENCES tasks(id),
  FOREIGN KEY (successor_id) REFERENCES tasks(id)
);
```

**Sources:** [kroolo.com](https://kroolo.com), [databasesample.com](https://databasesample.com), [stackexchange.com](https://stackexchange.com)

---

## 2. View Types Implementation

### 2.1 Table View

**Best Practices:**
- Sortable columns (name, due date, priority, assignee, progress)
- Inline editing for quick status updates
- Bulk actions (assign, change status, delete)
- Row actions menu (edit, delete, duplicate, convert to subtask)
- Advanced filters (by status, assignee, date range, tags)
- Column customization (show/hide based on user preference)

**Essential Columns:**
- Task name (with hierarchical indentation)
- Status (color-coded badge)
- Priority (icon-based)
- Assignee (avatar + name)
- Progress bar (visual %)
- Planned dates (start → end)
- Actions (icon buttons)

### 2.2 Kanban Board

**Recommended Libraries (2024-2025):**

| Library | Framework | Best For |
|---------|-----------|----------|
| **dnd-kit** | React | Performance, accessibility, modern |
| **react-beautiful-dnd** | React | Simple implementation, smooth UX |
| **SortableJS** | Vanilla/Agnostic | Framework-agnostic, lightweight |
| **Native HTML5 DnD** | Any | Maximum control, zero dependencies |
| **Alpine.js + SortableJS** | Alpine/Livewire | PHP stack integration |

**Best Practices:**
- **Accessibility (WCAG Level A):** Keyboard navigation for all drag-drop
- **WAI-ARIA attributes:** `aria-grabbed`, `aria-dropeffect`, `aria-label`
- **Visual feedback:** Highlight drop targets, show placement preview
- **State persistence:** Save column order in DB or localStorage
- **Performance:** Virtual scrolling for 100+ cards
- **Touch support:** Mobile-friendly drag-drop

**Column Configuration (Typical):**
- Backlog → To Do → In Progress → Review → Done
- Customizable per project/team

**Sources:** [dnd-kit.com](https://dnd-kit.com), [sortablejs.github.io](https://sortablejs.github.io), [w3.org/WAI/WCAG](https://www.w3.org/WAI/WCAG)

### 2.3 Gantt Chart

**Top Libraries (2025):**

| Library | License | Key Features | Framework Support |
|---------|---------|--------------|-------------------|
| **DHTMLX Gantt** | Commercial | Critical path, resources, export | React, Vue, Angular |
| **Bryntum Gantt** | Commercial | Enterprise-grade, constraints | React, Vue, Angular |
| **Syncfusion Gantt** | Free (Community) | Virtual scrolling, export | React, Vue, Angular |
| **Frappe Gantt** | MIT (Open-source) | Lightweight, basic features | Vanilla/Any |
| **Highcharts Gantt** | Commercial | TypeScript-friendly | React, Vue, Angular |

**For Laravel/Livewire Stack:**
- **Recommended:** DHTMLX Gantt (PHP-friendly API) or Frappe Gantt (lightweight)
- **Alternative:** Custom implementation with Canvas API for full control

**Key Features to Implement:**
- Task bars (colored by status/assignee)
- Dependency lines (arrows between tasks)
- Milestones (diamond markers)
- Today line (vertical indicator)
- Zoom levels (day/week/month views)
- Drag-drop to reschedule
- Critical path highlighting

**Sources:** [dhtmlx.com](https://dhtmlx.com), [bryntum.com](https://bryntum.com), [github.com/frappe/gantt](https://github.com/frappe/gantt)

---

## 3. Progress Management

### 3.1 Completion % Calculation

**Multiple Methods (Choose based on task type):**

1. **Effort-Based (Default):**
   ```
   progress = actual_hours / (actual_hours + remaining_hours) * 100
   ```

2. **Duration-Based:**
   ```
   progress = (actual_duration / total_duration) * 100
   ```

3. **Subtask Aggregation (For parent tasks):**
   ```
   progress = SUM(child.progress * child.weight) / SUM(child.weight)
   ```

4. **Manual Override (User-set):**
   - Allow users to set % directly
   - Add `progress_updated_at` timestamp

5. **Milestone-Based:**
   - Define milestones (0%, 25%, 50%, 75%, 100%)
   - Auto-update when milestones reached

**Recommendation:** Use subtask aggregation for parent tasks, effort-based for leaf tasks.

### 3.2 Schedule Variance Tracking

**Earned Value Management (EVM) Metrics:**

```
Planned Value (PV) = Budgeted cost of work scheduled
Earned Value (EV) = Budgeted cost of work completed
Schedule Variance (SV) = EV - PV
SV % = (SV / PV) * 100
```

**Interpretation:**
- **SV > 0**: Ahead of schedule
- **SV < 0**: Behind schedule
- **SV = 0**: On schedule

**Implementation:**
- Calculate SV weekly for each project
- Display variance indicator in dashboard
- Alert when SV < -10% (significantly behind)

**Sources:** [projectmanagementacademy.net](https://projectmanagementacademy.net), [pmi.org](https://pmi.org)

### 3.3 Delay Handling

**Delay Detection Logic:**
```php
if (now() > planned_end_date && status != 'done') {
    $is_overdue = true;
    $days_overdue = now()->diffInDays(planned_end_date);
}
```

**Delay Severity Levels:**
- **Warning:** 1-3 days overdue
- **Critical:** 4-7 days overdue
- **Severe:** 8+ days overdue

**Automated Actions:**
- Send notifications to assignee + PM
- Update dashboard "Overdue Tasks" count
- Escalate to stakeholders after X days

**Manual Mitigation Options:**
- Adjust planned_end_date (with reason)
- Reassign to different user
- Split into smaller tasks
- Mark as blocked (create dependency)

**Sources:** [productive.io](https://productive.io), [wrike.com](https://wrike.com), [clickup.com](https://clickup.com)

---

## 4. Notification Patterns

### 4.1 Overdue Task Alerts

**Best Practices:**

| Aspect | Implementation |
|--------|---------------|
| **Timing** | Instant alert + daily/weekly summaries |
| **Recipients** | Task owner (primary), PM, stakeholders (escalation) |
| **Channels** | In-app + Email + Slack/Teams (integration) |
| **Content** | Task name, due date, assignee, link to task, suggested action |
| **Frequency** | Customizable (once, daily, weekly) based on priority |

**Notification Triggers:**
1. Task becomes overdue (instant)
2. Daily digest at 9 AM (all overdue tasks)
3. Escalation after 3 days (notify PM)
4. Critical escalation after 7 days (notify stakeholders)

**Sources:** [clickup.com/blog/task-management](https://clickup.com), [larksuite.com](https://larksuite.com), [taskray.com](https://taskray.com)

### 4.2 Prevention Alerts (Proactive)

**Upcoming Deadline Warnings:**
- 3 days before due date (yellow badge)
- 1 day before due date (orange badge)
- On due date (red badge if incomplete)

**Progress Stagnation Alerts:**
- No progress for 7 days (notify PM)
- No status change for 14 days (escalate)

**Sources:** [teamgantt.com](https://teamgantt.com), [edworking.com](https://edworking.com)

---

## 5. Technical Recommendations for Polirium

### 5.1 Tech Stack Alignment

**For Laravel + Livewire + Alpine.js:**

| Feature | Library/Approach | Rationale |
|---------|------------------|-----------|
| **Table View** | PowerGrid component | Already in stack, feature-rich |
| **Kanban** | Alpine.js + SortableJS | Lightweight, Livewire-compatible |
| **Gantt** | DHTMLX Gantt (standard) or custom Canvas | PHP-friendly API |
| **Drag-Drop** | Native HTML5 + Alpine | No extra deps, full control |
| **Real-time** | Livewire echo/polling | Already integrated |

### 5.2 Database Schema Summary

```sql
-- Core tables
projects (id, name, client_id, status, ...)
tasks (id, parent_id, project_id, status, progress%, planned_dates, actual_dates, ...)
task_dependencies (predecessor_id, successor_id, type, lag)

-- Metadata
task_comments (task_id, user_id, content, created_at)
task_attachments (task_id, file_path, uploaded_by)
task_time_logs (task_id, user_id, hours, date, description)
```

### 5.3 Date Field Logic

**Field Semantics:**
- `planned_start_date`: Expected start (set during planning)
- `planned_end_date`: Expected completion (due date)
- `actual_start_date`: When work actually began (auto-set on status→in_progress)
- `actual_end_date`: When completed (auto-set on status→done)

**Validation Rules:**
- `actual_start` ≥ `planned_start` (can start early, not before plan)
- `actual_end` ≥ `actual_start` (must end after start)
- If both dates NULL → Not started
- If only `actual_start` set → In progress

---

## 6. Unresolved Questions

1. **Progress % calculation:** Should we use effort-based, duration-based, or hybrid approach for different task types?
2. **Gantt chart licensing:** Open-source (Frappe) vs commercial (DHTMLX) for MVP?
3. **Real-time updates:** WebSocket vs polling for collaborative editing?
4. **Mobile support:** Priority for mobile Kanban/Gantt views?
5. **Multi-project tasks:** Should tasks belong to multiple projects (many-to-many)?

---

## 7. Sources

- **Data Models:** kroolo.com, databasesample.com, stackexchange.com, ones.com
- **Progress Calculation:** parallelprojecttraining.com, projectplan365.com, rebelsguidetopm.com
- **Kanban Libraries:** dnd-kit.com, sortablejs.github.io, react-beautiful-dnd, w3.org/WAI
- **Gantt Libraries:** dhtmlx.com, bryntum.com, syncfusion.com, github.com/frappe/gantt
- **Schedule Variance:** projectmanagementacademy.net, pmi.org, productive.io
- **Notifications:** clickup.com, larksuite.com, taskray.com, teamgantt.com

**Research Period:** 2024-2025 best practices
**Tools Referenced:** Jira, Asana, ClickUp, Linear, Monday.com, Notion
