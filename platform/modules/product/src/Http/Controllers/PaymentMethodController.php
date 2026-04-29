<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;

class PaymentMethodController extends BaseController
{
    public function index()
    {
        return view('modules/product::payment.method.index');
    }
}
