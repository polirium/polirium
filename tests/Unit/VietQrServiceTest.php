<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Polirium\Modules\Product\Support\VietQrService;

class VietQrServiceTest extends TestCase
{
    public function test_builds_vietqr_image_url(): void
    {
        require_once dirname(__DIR__, 2) . '/platform/modules/product/src/Support/VietQrService.php';

        $url = VietQrService::imageUrl([
            'account_number' => '0123456789',
            'bank_code' => 'VCB',
            'account_holder' => 'NGUYEN VAN A',
            'store_name' => 'Polirium',
            'template' => 'compact',
            'show_info' => true,
            'full_account' => true,
        ], 150000, 'Thanh toan HD 001');

        $this->assertStringStartsWith('https://vietqr.app/img?', $url);
        $this->assertStringContainsString('acc=0123456789', $url);
        $this->assertStringContainsString('bank=VCB', $url);
        $this->assertStringContainsString('amount=150000', $url);
        $this->assertStringContainsString('des=Thanh+toan+HD+001', $url);
        $this->assertStringContainsString('template=compact', $url);
        $this->assertStringContainsString('holder=NGUYEN+VAN+A', $url);
    }

    public function test_sanitizes_description(): void
    {
        require_once dirname(__DIR__, 2) . '/platform/modules/product/src/Support/VietQrService.php';

        $this->assertSame('Thanh toan HD-01', VietQrService::sanitizeDescription('Thanh toan HD-01!!!'));
    }
}
