<?php

namespace Polirium\Modules\Vendor\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class TransferController extends BaseController
{
    public function index()
    {
        $this->authorize('vendors.transfers.index');
        return view('modules/vendor::transfer.index.index');
    }

    public function transfer(int $id = 0)
    {
        $this->authorize('vendors.transfers.edit');
        return view('modules/vendor::transfer.transfer.index', compact('id'));
    }
}
