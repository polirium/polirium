<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Installer\Support\InstallationProgress;
use PHPUnit\Framework\TestCase;

class InstallationProgressTest extends TestCase
{
    public function test_it_persists_percentage_status_and_safe_messages(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'polirium-installer-progress-');
        $progress = new InstallationProgress($path);

        try {
            $progress->reset('Bắt đầu cài đặt.');
            $progress->report('Đang tạo cấu trúc dữ liệu.', 35);
            $progress->complete('Cài đặt dữ liệu hoàn tất.');

            $snapshot = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame('complete', $snapshot['status']);
            $this->assertSame(100, $snapshot['percent']);
            $this->assertSame([
                'Bắt đầu cài đặt.',
                'Đang tạo cấu trúc dữ liệu.',
                'Cài đặt dữ liệu hoàn tất.',
            ], $snapshot['messages']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }

            if (is_file($path . '.tmp')) {
                unlink($path . '.tmp');
            }
        }
    }
}
