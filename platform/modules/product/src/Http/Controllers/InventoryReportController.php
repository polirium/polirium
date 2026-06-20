<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Polirium\Core\Base\Http\Controllers\BaseController;

class InventoryReportController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorize('products.stock.index');

        $today = now();
        $period = $request->string('period')->toString();
        [$from, $to] = match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'last_7_days' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$request->date('from')?->startOfDay() ?? $today->copy()->startOfMonth(), $request->date('to')?->endOfDay() ?? $today->copy()->endOfDay()],
        };
        $branchId = user_branch();
        $mode = $request->string('mode')->toString() ?: 'movement';

        $logs = DB::table('product_branches')
            ->join('products', 'products.id', '=', 'product_branches.product_id')
            ->leftJoin('product_logs', function ($join) use ($to) {
                $join->on('product_logs.product_id', '=', 'products.id')
                    ->whereColumn('product_logs.branch_id', 'product_branches.branch_id')
                    ->where('product_logs.created_at', '<=', $to);
            })
            ->when($branchId, fn ($query) => $query->where('product_branches.branch_id', $branchId))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q
                ->where('products.code', 'like', '%' . $request->string('search') . '%')
                ->orWhere('products.name', 'like', '%' . $request->string('search') . '%')))
            ->selectRaw('products.id, products.code, products.name, products.unit, products.cost, product_branches.qty AS current_qty,
                SUM(CASE WHEN product_logs.created_at < ? THEN CASE WHEN product_logs.direction = \'in\' THEN product_logs.amount ELSE -product_logs.amount END ELSE 0 END) AS opening_qty,
                SUM(CASE WHEN product_logs.created_at BETWEEN ? AND ? AND product_logs.direction = \'in\' THEN product_logs.amount ELSE 0 END) AS inbound_qty,
                SUM(CASE WHEN product_logs.created_at BETWEEN ? AND ? AND product_logs.direction = \'out\' THEN product_logs.amount ELSE 0 END) AS outbound_qty', [$from, $from, $to, $from, $to])
            ->groupBy('products.id', 'products.code', 'products.name', 'products.unit', 'products.cost', 'product_branches.branch_id', 'product_branches.qty')
            ->orderBy('products.code')
            ->get();

        $valueRows = DB::table('product_branches')
            ->join('products', 'products.id', '=', 'product_branches.product_id')
            ->when($branchId, fn ($query) => $query->where('product_branches.branch_id', $branchId))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q
                ->where('products.code', 'like', '%' . $request->string('search') . '%')
                ->orWhere('products.name', 'like', '%' . $request->string('search') . '%')))
            ->select('products.code', 'products.name', 'products.unit', 'products.price', 'products.cost', 'product_branches.qty')
            ->orderBy('products.code')
            ->get();

        return view('modules/product::report.inventory-movement', compact('logs', 'valueRows', 'mode', 'from', 'to'));
    }
}
