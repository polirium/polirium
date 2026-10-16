<?php

declare(strict_types=1);

namespace Tests\Unit;

use Polirium\VietnamAdministrativeUnits\Data\OfficialAdministrativeUnits;
use Tests\TestCase;

class OfficialAdministrativeUnitsTest extends TestCase
{
    public function test_official_dataset_has_expected_unique_codes_and_valid_parents(): void
    {
        $dataset = new OfficialAdministrativeUnits();
        $provinces = $dataset->provinces();
        $wards = iterator_to_array($dataset->wards(), false);

        $this->assertCount(34, $provinces);
        $this->assertCount(3321, $wards);
        $this->assertCount(3321, array_unique(array_column($wards, 'code')));

        foreach ($wards as $ward) {
            $this->assertMatchesRegularExpression('/^\d{5}$/', $ward['code']);
            $this->assertArrayHasKey($ward['province_code'], $provinces);
            $this->assertContains($ward['type'], ['phường', 'xã', 'đặc khu']);
        }
    }
}
