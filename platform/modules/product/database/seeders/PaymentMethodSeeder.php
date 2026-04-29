<?php

namespace Polirium\Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            [
                'name' => 'Tiền mặt',
                'code' => 'cash',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'Chuyển khoản',
                'code' => 'bank',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Thẻ',
                'code' => 'card',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'COD',
                'code' => 'cod',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Khác',
                'code' => 'other',
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
