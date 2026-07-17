<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;

class BankAccountController extends BaseController
{
    public function index()
    {
        abort_unless((int) auth()->id() === 1, 403);

        return view('modules/product::payment.bank-account.index');
    }
}
