<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Illuminate\Routing\Controller;

class DeliveryPartnerController extends Controller
{
    public function index()
    {
        return view('modules/product::payment.delivery-partner.index');
    }
}
