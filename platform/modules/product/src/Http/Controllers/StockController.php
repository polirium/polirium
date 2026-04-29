<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;

class StockController extends BaseController
{
    public function index()
    {
        $this->authorize('products.stock.index');

        return view('modules/product::stock.index.index');
    }

    public function show($id)
    {
        $this->authorize('products.stock.view');

        $viewMode = true;

        return view('modules/product::stock.stock.index', compact('id', 'viewMode'));
    }

    public function stock($id = null)
    {
        $this->authorize('products.stock.manage');

        return view('modules/product::stock.stock.index', compact('id'));
    }
}
