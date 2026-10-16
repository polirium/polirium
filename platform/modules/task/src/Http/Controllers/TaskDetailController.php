<?php

namespace Polirium\Modules\Task\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Polirium\Core\Base\Http\Controllers\BaseController;
use Polirium\Modules\Task\Models\Task;
use Polirium\Modules\Task\Models\TaskAttachment;
use Polirium\Modules\Task\Models\TaskComment;
use Polirium\Modules\Task\Models\TaskDependency;
use Polirium\Modules\Task\Models\TaskTimeLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comments, time logs, attachments and dependencies of a task.
 */
class TaskDetailController extends BaseController
{
    private const DISK = 'local';

    public function storeComment(Request $request, $id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $data = $request->validate(['content' => ['required', 'string', 'max:5000']], [], $this->attributes());

        $task->comments()->create(['user_id' => auth()->id(), 'content' => $data['content']]);

        return $this->backToTask($task, 'comments', __('modules/task::task.comment_added'));
    }

    public function destroyComment($id, $commentId): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $comment = TaskComment::where('task_id', $task->id)->findOrFail($commentId);
        $this->authorizeOwnerOrEditor($comment->user_id);
        $comment->delete();

        return $this->backToTask($task, 'comments', __('modules/task::task.comment_deleted'));
    }

    public function storeTimeLog(Request $request, $id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $data = $request->validate([
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'log_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [], $this->attributes());

        DB::transaction(function () use ($task, $data) {
            $task->timeLogs()->create($data + ['user_id' => auth()->id()]);
            $this->syncActualHours($task);
        });

        return $this->backToTask($task, 'time', __('modules/task::task.time_logged'));
    }

    public function destroyTimeLog($id, $logId): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $log = TaskTimeLog::where('task_id', $task->id)->findOrFail($logId);
        $this->authorizeOwnerOrEditor($log->user_id);

        DB::transaction(function () use ($task, $log) {
            $log->delete();
            $this->syncActualHours($task);
        });

        return $this->backToTask($task, 'time', __('modules/task::task.time_log_deleted'));
    }

    public function storeAttachment(Request $request, $id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip'],
        ], [], $this->attributes());

        $file = $request->file('file');
        $path = $file->store('task-attachments/' . $task->id, self::DISK);

        $task->attachments()->create([
            'file_path' => $path,
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'file_size' => $file->getSize(),
            'mime_type' => mb_substr((string) $file->getMimeType(), 0, 100),
            'uploaded_by' => auth()->id(),
        ]);

        return $this->backToTask($task, 'files', __('modules/task::task.attachment_uploaded'));
    }

    public function downloadAttachment($id, $attachmentId): StreamedResponse
    {
        $task = Task::findVisibleOrFail($id);
        $attachment = TaskAttachment::where('task_id', $task->id)->findOrFail($attachmentId);
        abort_unless(Storage::disk(self::DISK)->exists($attachment->file_path), 404);

        return Storage::disk(self::DISK)->download($attachment->file_path, $attachment->file_name);
    }

    public function destroyAttachment($id, $attachmentId): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $attachment = TaskAttachment::where('task_id', $task->id)->findOrFail($attachmentId);
        $this->authorizeOwnerOrEditor($attachment->uploaded_by);

        Storage::disk(self::DISK)->delete($attachment->file_path);
        $attachment->delete();

        return $this->backToTask($task, 'files', __('modules/task::task.attachment_deleted'));
    }

    /**
     * The current task depends on (waits for) the chosen predecessor.
     */
    public function storeDependency(Request $request, $id): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        $data = $request->validate([
            'predecessor_id' => ['required', 'integer', Rule::notIn([$task->id])],
            'dependency_type' => ['required', Rule::in(['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])],
            'lag_days' => ['nullable', 'integer', 'min:-365', 'max:365'],
        ], [], $this->attributes());

        $predecessor = Task::findVisibleOrFail($data['predecessor_id']);

        if (TaskDependency::where('predecessor_id', $predecessor->id)->where('successor_id', $task->id)->exists()) {
            return back()->withErrors(['predecessor_id' => __('modules/task::task.dependency_exists')])->withInput();
        }

        if ($this->reaches($task->id, $predecessor->id)) {
            return back()->withErrors(['predecessor_id' => __('modules/task::task.dependency_cycle')])->withInput();
        }

        TaskDependency::create([
            'predecessor_id' => $predecessor->id,
            'successor_id' => $task->id,
            'dependency_type' => $data['dependency_type'],
            'lag_days' => (int) ($data['lag_days'] ?? 0),
        ]);

        return $this->backToTask($task, 'dependencies', __('modules/task::task.dependency_added'));
    }

    public function destroyDependency($id, $dependencyId): RedirectResponse
    {
        $task = Task::findVisibleOrFail($id);
        TaskDependency::where(fn ($q) => $q->where('successor_id', $task->id)->orWhere('predecessor_id', $task->id))
            ->findOrFail($dependencyId)
            ->delete();

        return $this->backToTask($task, 'dependencies', __('modules/task::task.dependency_deleted'));
    }

    /**
     * Whether $to is already reachable from $from by following "predecessor -> successor".
     */
    private function reaches(int $from, int $to): bool
    {
        $seen = [];
        $frontier = [$from];

        while ($frontier !== []) {
            if (in_array($to, $frontier, true)) {
                return true;
            }

            $seen = array_merge($seen, $frontier);
            $frontier = TaskDependency::whereIn('predecessor_id', $frontier)
                ->whereNotIn('successor_id', $seen)
                ->pluck('successor_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return false;
    }

    private function syncActualHours(Task $task): void
    {
        $task->forceFill(['actual_hours' => (float) $task->timeLogs()->sum('hours'), 'updated_by' => auth()->id()])->save();
    }

    private function authorizeOwnerOrEditor(?int $ownerId): void
    {
        abort_unless((int) $ownerId === (int) auth()->id() || auth()->user()->can('tasks.edit'), 403);
    }

    private function backToTask(Task $task, string $tab, string $message): RedirectResponse
    {
        return redirect()->to(route('admin.tasks.show', $task->id) . '?tab=' . $tab . '#task-tabs')->with('success', $message);
    }

    private function attributes(): array
    {
        return trans('modules/task::task.attributes');
    }
}
