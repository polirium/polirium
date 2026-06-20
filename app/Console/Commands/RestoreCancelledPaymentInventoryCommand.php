<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\ProductLog;

class RestoreCancelledPaymentInventoryCommand extends Command
{
    protected $signature = 'inventory:restore-cancelled-payment {code : Mã hóa đơn đã hủy, ví dụ BH/01614}';

    protected $description = 'Hoàn tồn kho cho một hóa đơn đã hủy nhưng chưa có log hoàn kho';

    public function handle(): int
    {
        $payment = Payment::query()
            ->with('products.product')
            ->where('code', $this->argument('code'))
            ->first();

        if (! $payment) {
            $this->error('Không tìm thấy hóa đơn.');

            return self::FAILURE;
        }

        if (! in_array($payment->status, ['cancel', 'cancelled', 'delivery_failed'], true)) {
            $this->error('Chỉ có thể hoàn tồn cho hóa đơn đã hủy hoặc không giao được.');

            return self::FAILURE;
        }

        $restored = DB::transaction(function () use ($payment): int {
            $restored = 0;

            foreach ($payment->products as $item) {
                $hasRestockLog = ProductLog::query()
                    ->where('product_id', $item->product_id)
                    ->where('productable_type', Payment::class)
                    ->where('productable_id', $payment->id)
                    ->whereColumn('amount_after', '>', 'amount_before')
                    ->exists();

                if ($hasRestockLog) {
                    continue;
                }

                product_logs(
                    $item->product_id,
                    $payment->id,
                    Payment::class,
                    $item->amount,
                    $item->product?->cost ?? 0,
                    0,
                    true,
                    $payment->branch_id
                );

                $restored++;
            }

            return $restored;
        });

        if ($restored === 0) {
            $this->warn('Hóa đơn đã có log hoàn kho; không thay đổi tồn kho.');

            return self::SUCCESS;
        }

        $this->info("Đã hoàn tồn kho cho {$restored} dòng hàng của hóa đơn {$payment->code}.");

        return self::SUCCESS;
    }
}
