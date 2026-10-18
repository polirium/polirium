<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Polirium\Modules\Product\Http\Model\Payment\Payment as CustomerPayment;
use Polirium\Modules\Product\Http\Model\Refund\Refund as CustomerRefund;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase as VendorPurchase;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund as VendorRefund;

class AuditInventoryCommand extends Command
{
    protected $signature = 'audit:inventory
                            {--product= : Mã sản phẩm cụ thể (VD: XG.741100)}
                            {--fix : Đồng bộ tồn kho hiện tại theo dòng thẻ kho cuối của từng chi nhánh (không sửa lịch sử)}';

    protected $description = 'Kiểm tra toàn bộ tính toán Thẻ Kho (product_logs) và Tồn Kho (products.qty / product_branches.qty)';

    private int $totalProducts = 0;

    private int $passedProducts = 0;

    private int $failedProducts = 0;

    private array $errors = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║          KIỂM TRA TOÀN VẸN DỮ LIỆU TỒN KHO           ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Kiểm tra Orphan Records
        $this->checkOrphans();

        // 2. Kiểm tra toán học Thẻ Kho
        $this->checkMath();

        // 3. Tổng kết
        $this->printSummary();

        return $this->failedProducts > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Kiểm tra các bản ghi "mồ côi" (orphan records).
     */
    private function checkOrphans(): void
    {
        $this->info('━━━ BƯỚC 1: Kiểm tra bản ghi mồ côi (Orphan Records) ━━━');
        $this->info('');

        // Payment logs trỏ tới Payment đã bị xóa
        $orphanPaymentLogs = DB::table('product_logs')
            ->where('productable_type', CustomerPayment::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_payments')
                    ->whereColumn('product_payments.id', 'product_logs.productable_id');
            })
            ->count();

        // Refund logs trỏ tới Refund đã bị xóa
        $orphanRefundLogs = DB::table('product_logs')
            ->where('productable_type', CustomerRefund::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_refunds')
                    ->whereColumn('product_refunds.id', 'product_logs.productable_id');
            })
            ->count();

        // Purchase logs trỏ tới Purchase đã bị xóa
        $orphanPurchaseLogs = DB::table('product_logs')
            ->where('productable_type', VendorPurchase::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vendor_purchases')
                    ->whereColumn('vendor_purchases.id', 'product_logs.productable_id');
            })
            ->count();

        // Refund records trỏ tới Payment đã bị xóa
        $orphanRefunds = DB::table('product_refunds')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_payments')
                    ->whereColumn('product_payments.id', 'product_refunds.product_payment_id');
            })
            ->count();

        $totalOrphans = $orphanPaymentLogs + $orphanRefundLogs + $orphanPurchaseLogs + $orphanRefunds;

        if ($totalOrphans === 0) {
            $this->line('  <fg=green>✓</> Không có bản ghi mồ côi nào.');
        } else {
            $this->line("  <fg=red>✗</> Phát hiện <fg=red;options=bold>{$totalOrphans}</> bản ghi mồ côi:");
            if ($orphanPaymentLogs > 0) {
                $this->line("    └─ <fg=yellow>{$orphanPaymentLogs}</> log Hóa đơn trỏ tới HĐ đã xóa");
            }
            if ($orphanRefundLogs > 0) {
                $this->line("    └─ <fg=yellow>{$orphanRefundLogs}</> log Trả hàng trỏ tới phiếu đã xóa");
            }
            if ($orphanPurchaseLogs > 0) {
                $this->line("    └─ <fg=yellow>{$orphanPurchaseLogs}</> log Nhập hàng trỏ tới đơn đã xóa");
            }
            if ($orphanRefunds > 0) {
                $this->line("    └─ <fg=yellow>{$orphanRefunds}</> phiếu Trả hàng trỏ tới HĐ đã xóa");
            }

            // FIX: Xóa orphan records nếu có flag --fix
            if ($this->option('fix')) {
                $this->line('');
                $this->line('  <fg=cyan>→</> Đang xóa bản ghi mồ côi...');

                // Xóa orphan payment logs
                if ($orphanPaymentLogs > 0) {
                    $deleted = DB::table('product_logs')
                        ->where('productable_type', CustomerPayment::class)
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('product_payments')
                                ->whereColumn('product_payments.id', 'product_logs.productable_id');
                        })
                        ->delete();
                    $this->line("    <fg=green>✓</> Đã xóa {$deleted} log Hóa đơn mồ côi");
                }

                // Xóa orphan refund logs
                if ($orphanRefundLogs > 0) {
                    $deleted = DB::table('product_logs')
                        ->where('productable_type', CustomerRefund::class)
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('product_refunds')
                                ->whereColumn('product_refunds.id', 'product_logs.productable_id');
                        })
                        ->delete();
                    $this->line("    <fg=green>✓</> Đã xóa {$deleted} log Trả hàng mồ côi");
                }

                // Xóa orphan purchase logs
                if ($orphanPurchaseLogs > 0) {
                    $deleted = DB::table('product_logs')
                        ->where('productable_type', VendorPurchase::class)
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('vendor_purchases')
                                ->whereColumn('vendor_purchases.id', 'product_logs.productable_id');
                        })
                        ->delete();
                    $this->line("    <fg=green>✓</> Đã xóa {$deleted} log Nhập hàng mồ côi");
                }

                // Xóa orphan refunds
                if ($orphanRefunds > 0) {
                    $deleted = DB::table('product_refunds')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('product_payments')
                                ->whereColumn('product_payments.id', 'product_refunds.product_payment_id');
                        })
                        ->delete();
                    $this->line("    <fg=green>✓</> Đã xóa {$deleted} phiếu Trả hàng mồ côi");
                }

                $this->line('    <fg=green>✓</> Hoàn tất xóa bản ghi mồ côi!');
            } else {
                $this->errors[] = "Tồn tại {$totalOrphans} bản ghi mồ côi cần xóa.";
            }
        }

        // Kiểm tra đơn Hủy thiếu log Hoàn kho
        $cancelledPayments = DB::table('product_payments')
            ->whereIn('status', ['cancel', 'cancelled', 'delivery_failed'])
            ->get();

        $missingCancelLogs = 0;

        foreach ($cancelledPayments as $payment) {
            $products = DB::table('product_payment_products')
                ->where('product_payment_id', $payment->id)
                ->get();

            foreach ($products as $item) {
                $logCount = DB::table('product_logs')
                    ->where('product_id', $item->product_id)
                    ->where('productable_type', CustomerPayment::class)
                    ->where('productable_id', $payment->id)
                    ->count();

                if ($logCount === 1) {
                    $missingCancelLogs++;
                }
            }
        }

        if ($missingCancelLogs > 0) {
            $this->line("  <fg=red>✗</> <fg=red;options=bold>{$missingCancelLogs}</> đơn Hủy thiếu log Hoàn kho (+)");
            $this->errors[] = "{$missingCancelLogs} đơn Hủy thiếu log hoàn kho.";
        } else {
            $this->line('  <fg=green>✓</> Tất cả đơn Hủy đều có đủ log Hoàn kho (+).');
        }

        $this->info('');
    }

    /**
     * Kiểm tra toán học chuỗi Thẻ Kho và đối chiếu với qty hiện tại.
     */
    private function checkMath(): void
    {
        $this->info('━━━ BƯỚC 2: Kiểm tra toán học Thẻ Kho ━━━');
        $this->info('');

        $productCode = $this->option('product');

        if ($productCode) {
            $product = DB::table('products')->where('code', $productCode)->first();
            if (! $product) {
                $this->error("  Không tìm thấy sản phẩm với mã: {$productCode}");

                return;
            }
            $productIds = collect([$product->id]);
        } else {
            $productIds = DB::table('product_logs')->distinct()->pluck('product_id');
        }

        $this->totalProducts = $productIds->count();
        $bar = $this->output->createProgressBar($this->totalProducts);
        $bar->setFormat("  Đang kiểm tra: %current%/%max% sản phẩm [%bar%] %percent%%\n");
        $bar->start();

        $failedRows = [];

        foreach ($productIds as $productId) {
            $bar->advance();

            $product = DB::table('products')->where('id', $productId)->first();
            if (! $product) {
                continue;
            }

            // Stock card lines are kept per branch: amount_before/amount_after
            // continue the previous line of the same branch.
            $logsByBranch = DB::table('product_logs')
                ->where('product_id', $productId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($log) => (int) $log->branch_id);

            if ($logsByBranch->isEmpty()) {
                continue;
            }

            $branchRows = DB::table('product_branches')->where('product_id', $productId)->pluck('qty', 'branch_id')->map(fn ($qty) => (int) $qty);
            $issues = [];
            $chainErrors = [];
            $cardTotal = 0;

            foreach ($logsByBranch as $branchId => $logs) {
                $previous = null;

                foreach ($logs as $log) {
                    $increase = $log->direction !== null
                        ? $log->direction === 'in'
                        : (int) $log->amount_after >= (int) $log->amount_before;
                    $expectedAfter = (int) $log->amount_before + ($increase ? 1 : -1) * abs((int) $log->amount);

                    if ($previous !== null && (int) $log->amount_before !== (int) $previous->amount_after) {
                        $chainErrors[] = "Chi nhánh {$branchId}, log #{$log->id}: tồn trước {$log->amount_before} ≠ tồn sau dòng trước {$previous->amount_after}";
                    }
                    if ((int) $log->amount_after !== $expectedAfter) {
                        $chainErrors[] = "Chi nhánh {$branchId}, log #{$log->id}: {$log->amount_before} " . ($increase ? '+' : '-') . " {$log->amount} ≠ {$log->amount_after}";
                    }

                    $previous = $log;
                }

                $cardQty = (int) $previous->amount_after;
                $cardTotal += $cardQty;
                $branchQty = $branchRows[$branchId] ?? 0;

                if ($branchQty !== $cardQty) {
                    $issues[] = "chi nhánh {$branchId}: tồn={$branchQty} ≠ thẻ kho={$cardQty}";

                    // Only the current quantity is aligned to the stock card;
                    // history is never rewritten.
                    if ($this->option('fix')) {
                        DB::table('product_branches')->where('product_id', $productId)->where('branch_id', $branchId)->update(['qty' => $cardQty]);
                    }
                }
            }

            $branchTotal = (int) DB::table('product_branches')->where('product_id', $productId)->sum('qty');
            if ((int) $product->qty !== $branchTotal) {
                $issues[] = "products.qty={$product->qty} ≠ tổng chi nhánh={$branchTotal}";

                if ($this->option('fix')) {
                    DB::table('products')->where('id', $productId)->update(['qty' => $branchTotal]);
                }
            }

            if ($chainErrors !== []) {
                // Read-only: a broken chain needs the documents checked by hand.
                $issues[] = 'Chuỗi thẻ kho bị đứt (' . count($chainErrors) . ' dòng, không tự sửa)';
            }

            $fixed = $this->option('fix') && $chainErrors === [];

            if ($issues === [] || $fixed) {
                $this->passedProducts++;

                continue;
            }

            $this->failedProducts++;
            $failedRows[] = [
                $product->code ?? "ID:{$productId}",
                $product->name ?? '-',
                $cardTotal,
                $product->qty,
                $branchTotal,
                implode('; ', $issues),
            ];

            if ($productCode && $chainErrors !== []) {
                $bar->clear();
                $this->info('');
                $this->warn("  Chi tiết lỗi chuỗi cho {$product->code}:");
                foreach (array_slice($chainErrors, 0, 50) as $err) {
                    $this->line("    └─ <fg=red>{$err}</>");
                }
                $bar->display();
            }
        }

        $bar->finish();
        $this->info('');

        if (! empty($failedRows)) {
            $this->info('');
            $this->error('  Các sản phẩm có vấn đề:');
            $this->info('');
            $this->table(
                ['Mã SP', 'Tên SP', 'Theo thẻ kho', 'products.qty', 'Tổng chi nhánh', 'Vấn đề'],
                $failedRows
            );
        }
    }

    /**
     * In ra phần tổng kết.
     */
    private function printSummary(): void
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║                     KẾT QUẢ KIỂM TRA                   ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->line("  Tổng sản phẩm kiểm tra:  <fg=white;options=bold>{$this->totalProducts}</>");
        $this->line("  <fg=green>✓</> Đạt:                    <fg=green;options=bold>{$this->passedProducts}</>");

        if ($this->failedProducts > 0) {
            $this->line("  <fg=red>✗</> Lỗi:                    <fg=red;options=bold>{$this->failedProducts}</>");
        } else {
            $this->line('  <fg=red>✗</> Lỗi:                    <fg=green;options=bold>0</>');
        }

        if (! empty($this->errors)) {
            $this->info('');
            $this->warn('  Cảnh báo bổ sung:');
            foreach ($this->errors as $error) {
                $this->line("    └─ <fg=yellow>{$error}</>");
            }
        }

        $this->info('');

        if ($this->failedProducts === 0 && empty($this->errors)) {
            $this->info('  ✅ TOÀN BỘ DỮ LIỆU TỒN KHO CHÍNH XÁC 100%');
        } else {
            $this->warn('  ⚠️  PHÁT HIỆN SAI LỆCH. Chạy lại với --fix để tự động sửa:');
            $this->line('     <fg=cyan>php artisan audit:inventory --fix</>');
        }

        $this->info('');
    }
}
