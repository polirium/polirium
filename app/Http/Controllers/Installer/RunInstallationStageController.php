<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Installer\Steps\InstallPolirium;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RunInstallationStageController extends Controller
{
    public function __invoke(InstallPolirium $installer): JsonResponse
    {
        try {
            return response()->json($installer->runNextStage());
        } catch (RuntimeException $exception) {
            return response()->json([
                ...$installer->progressSnapshot(),
                'done' => false,
                'error' => $exception->getMessage(),
            ], 422);
        }
    }
}
