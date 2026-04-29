<?php

namespace Polirium\Modules\Vendor\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class VendorController extends BaseController
{
    public function index()
    {
        $this->authorize('vendors.index');
        return view('modules/vendor::index.index');
    }

    public function group()
    {
        $this->authorize('vendors.groups');
        return view('modules/vendor::vendor-group.index');
    }
}
