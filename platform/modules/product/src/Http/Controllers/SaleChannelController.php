<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Illuminate\Routing\Controller;

class SaleChannelController extends Controller
{
    public function index()
    {
        return view('modules/product::payment.sale-channel.index');
    }
}
