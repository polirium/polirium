<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Polirium\Modules\Product\Http\Model\Refund\Refund;

/**
 * POS returns saved before 1.1 kept total_payable_amount, value and the line
 * price / discount_value / value multiplied by 100. The app reads those rows
 * correctly as they are (money_unit IS NULL); this command only rewrites them
 * in VND so reports and SQL see one unit. Dry run unless --apply is given.
 */
class ConvertRefundMoneyCommand extends Command
{
    protected $signature = 'refunds:convert-money
                            {--id=* : Chỉ chuyển các phiếu trả hàng có ID này (product_refunds.id)}
                            {--all : Chuyển mọi phiếu trả hàng cũ}
                            {--apply : Ghi thay đổi (mặc định chỉ xem trước)}
                            {--force : Không hỏi xác nhận khi --apply}';

    protected $description = 'Chuyển số tiền của phiếu trả hàng POS cũ (lưu x100) sang VNĐ';

    public function handle(): int
    {
        $ids = array_filter(array_map('intval', (array) $this->option('id')));

        if (! $ids && ! $this->option('all')) {
            $this->error('Chọn phiếu cần chuyển: --id=<id> (có thể lặp lại) hoặc --all.');

            return self::FAILURE;
        }

        $query = Refund::query()
            ->whereNull('money_unit')
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('id');

        $refunds = $query->with('products')->get();

        if ($refunds->isEmpty()) {
            $this->info('Không có phiếu trả hàng cũ nào cần chuyển.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Mã', 'Tổng sau giảm SP', 'Tổng tiền trả', 'Số dòng'],
            $refunds->map(fn (Refund $refund) => [
                $refund->id,
                $refund->code,
                $this->change($refund->total_payable_amount),
                $this->change($refund->value),
                $refund->products->count(),
            ])->all()
        );

        $lines = $refunds->sum(fn (Refund $refund) => $refund->products->count());

        if (! $this->option('apply')) {
            $this->warn("Xem trước: {$refunds->count()} phiếu, {$lines} dòng hàng. Chưa ghi gì. Thêm --apply để chuyển.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Chuyển {$refunds->count()} phiếu và {$lines} dòng hàng sang VNĐ?")) {
            $this->warn('Đã hủy, không thay đổi gì.');

            return self::SUCCESS;
        }

        $converted = 0;
        $convertedLines = 0;

        DB::transaction(function () use ($refunds, &$converted, &$convertedLines) {
            foreach ($refunds as $refund) {
                // Re-read under lock: skip a row another run converted meanwhile.
                $locked = Refund::whereKey($refund->id)->whereNull('money_unit')->lockForUpdate()->first();
                if (! $locked) {
                    continue;
                }

                $convertedLines += DB::table('product_refund_products')
                    ->where('product_refund_id', $locked->id)
                    ->update([
                        'price' => DB::raw('ROUND(price / 100)'),
                        'discount_value' => DB::raw('ROUND(discount_value / 100)'),
                        'value' => DB::raw('ROUND(value / 100)'),
                    ]);

                DB::table('product_refunds')
                    ->where('id', $locked->id)
                    ->update([
                        'total_payable_amount' => (int) round($locked->total_payable_amount / 100),
                        'value' => (int) round($locked->value / 100),
                        'money_unit' => Refund::MONEY_UNIT_VND,
                    ]);

                $converted++;
            }
        });

        $this->info("Đã chuyển {$converted} phiếu, {$convertedLines} dòng hàng sang VNĐ.");

        return self::SUCCESS;
    }

    private function change(?int $stored): string
    {
        $stored = (int) $stored;

        return core_number_format($stored) . ' → ' . core_number_format((int) round($stored / 100));
    }
}
