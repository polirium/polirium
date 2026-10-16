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

        // Opening and closing come from the balances recorded on the stock card,
        // never from summing movements from zero: legacy history is incomplete.
        $balanceAt = fn (string $operator) => "(SELECT l.amount_after FROM product_logs l
            WHERE l.product_id = products.id AND l.branch_id = product_branches.branch_id AND l.created_at {$operator} ?
            ORDER BY l.created_at DESC, l.id DESC LIMIT 1)";
        $balanceAfter = fn (string $operator) => "(SELECT l.amount_before FROM product_logs l
            WHERE l.product_id = products.id AND l.branch_id = product_branches.branch_id AND l.created_at {$operator} ?
            ORDER BY l.created_at ASC, l.id ASC LIMIT 1)";

        $logs = DB::table('product_branches')
            ->join('products', 'products.id', '=', 'product_branches.product_id')
            ->whereNotIn('products.type', ['service', 'combo'])
            ->when($branchId, fn ($query) => $query->where('product_branches.branch_id', $branchId))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q
                ->where('products.code', 'like', '%' . $request->string('search') . '%')
                ->orWhere('products.name', 'like', '%' . $request->string('search') . '%')))
            ->selectRaw('products.id, products.code, products.name, products.unit, products.cost, product_branches.qty AS current_qty,
                COALESCE(' . $balanceAt('<') . ', ' . $balanceAfter('>=') . ', product_branches.qty) AS opening_qty,
                COALESCE(' . $balanceAt('<=') . ', ' . $balanceAfter('>') . ', product_branches.qty) AS closing_qty,
                (SELECT COALESCE(SUM(l.amount), 0) FROM product_logs l WHERE l.product_id = products.id AND l.branch_id = product_branches.branch_id
                    AND l.direction = \'in\' AND l.created_at BETWEEN ? AND ?) AS inbound_qty,
                (SELECT COALESCE(SUM(l.amount), 0) FROM product_logs l WHERE l.product_id = products.id AND l.branch_id = product_branches.branch_id
                    AND l.direction = \'out\' AND l.created_at BETWEEN ? AND ?) AS outbound_qty',
                [$from, $from, $to, $to, $from, $to, $from, $to])
            ->orderBy('products.code')
            ->get()
            ->each(function ($row) use ($to) {
                if ($to->isFuture() || $to->isToday()) {
                    $row->closing_qty = $row->current_qty;
                }

                foreach (['current_qty', 'opening_qty', 'closing_qty', 'inbound_qty', 'outbound_qty'] as $field) {
                    $row->{$field} = (int) $row->{$field};
                }

                // Gaps left by legacy history are shown, not silently absorbed.
                $row->adjustment_qty = $row->closing_qty - ($row->opening_qty + $row->inbound_qty - $row->outbound_qty);
            });

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
