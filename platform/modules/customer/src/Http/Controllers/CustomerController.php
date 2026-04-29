<?php

namespace Polirium\Modules\Customer\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;

class CustomerController extends BaseController
{
    public function index()
    {
        $this->authorize('customers.index');

        return view('modules/customer::index.index');
    }

    public function group()
    {
        $this->authorize('customers.groups');

        return view('modules/customer::customer-group.index');
    }
}
