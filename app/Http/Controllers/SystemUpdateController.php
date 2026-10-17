<?php

namespace App\Http\Controllers;

use App\Updater\ReleaseClient;
use App\Updater\Updater;
use App\Updater\UpdateException;
use App\Updater\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Settings → System update. Each stage is a separate request so a slow
 * download or migration never runs into one long request timeout.
 */
class SystemUpdateController extends Controller
{
    public function __construct(private readonly Updater $updater, private readonly ReleaseClient $releases)
    {
    }

    public function index(): View
    {
        $error = null;

        try {
            $latest = $this->releases->latest();
        } catch (Throwable $exception) {
            $latest = null;
            $error = $exception->getMessage();
        }

        $current = Version::current();

        return view('system.update', [
            'current' => $current,
            'latest' => $latest,
            'available' => $latest && ! empty($latest['version']) && Version::isNewer($latest['version'], $current),
            'error' => $error,
            'state' => $this->updater->state(),
            'history' => array_slice($this->updater->history(), 0, 10),
            'backup' => $this->updater->latestBackupInfo(),
        ]);
    }

    public function check(): RedirectResponse
    {
        try {
            $this->releases->latest(true);
        } catch (Throwable $exception) {
            return back()->with('error', __('system.check_failed', ['message' => $exception->getMessage()]));
        }

        return back();
    }

    /**
     * Start (when `version` is given) or continue the update by one stage.
     */
    public function stage(Request $request): JsonResponse
    {
        @set_time_limit(900);
        @ignore_user_abort(true);

        try {
            if ($request->filled('version')) {
                $version = $request->validate(['version' => ['required', 'string', 'max:50', 'regex:/^v?\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/']])['version'];
                $state = $this->updater->start($version);
            } else {
                $state = $this->updater->runNextStage();
            }
        } catch (UpdateException $exception) {
            return response()->json(['done' => false, 'error' => __('system.failed', ['message' => $exception->getMessage()])], 422);
        }

        return response()->json([
            'done' => ($state['status'] ?? null) === 'done',
            'next' => $state['next'] ?? null,
            'next_label' => isset($state['next']) ? __('system.stages.' . $state['next']) : null,
            'log' => array_column($state['log'] ?? [], 'message'),
            'message' => ($state['status'] ?? null) === 'done' ? __('system.done', ['version' => $state['to']]) : null,
        ]);
    }

    public function rollback(): RedirectResponse
    {
        try {
            $result = $this->updater->rollback();
        } catch (UpdateException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('system.rolled_back', ['version' => $result['to']]));
    }
}
