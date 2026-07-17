<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Illuminate\Routing\Controller;

class VietQrController extends Controller
{
    /**
     * Public VietQR generator — no auth required.
     */
    public function index()
    {
        return view('modules/product::payment.vietqr.public');
    }
}
