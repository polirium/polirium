<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("UPDATE product_logs l JOIN vendor_purchase_refunds r ON r.id = l.productable_id SET l.branch_id = r.branch_id WHERE l.branch_id IS NULL AND l.productable_type = 'Polirium\\Modules\\Vendor\\Http\\Model\\Refund\\Refund'");
        DB::statement("UPDATE product_logs l JOIN product_stocks s ON s.id = l.productable_id SET l.branch_id = s.branch_id WHERE l.branch_id IS NULL AND l.productable_type = 'Polirium\\Modules\\Product\\Http\\Model\\Stock\\Stock'");
        DB::statement("UPDATE product_logs l JOIN product_refunds r ON r.id = l.productable_id JOIN product_payments p ON p.id = r.product_payment_id SET l.branch_id = p.branch_id WHERE l.branch_id IS NULL AND l.productable_type = 'Polirium\\Modules\\Product\\Http\\Model\\Refund\\Refund'");

        // Historic transfers had an outgoing log only. Add the missing receipt at destination once.
        DB::statement("INSERT INTO product_logs (product_id, branch_id, productable_id, productable_type, amount, direction, value_before, value_after, amount_before, amount_after, created_at, updated_at)
            SELECT tp.product_id, t.to_branch_id, t.id, 'Polirium\\Modules\\Vendor\\Http\\Model\\Transfer\\Transfer', tp.amount, 'in', 0, 0, 0, 0, COALESCE(t.date_take, t.created_at), COALESCE(t.date_take, t.created_at)
            FROM vendor_transfer_products tp JOIN vendor_transfers t ON t.id = tp.vendor_transfer_id
            WHERE t.status = 'success' AND NOT EXISTS (
                SELECT 1 FROM product_logs l WHERE l.product_id = tp.product_id AND l.productable_id = t.id
                AND l.productable_type = 'Polirium\\Modules\\Vendor\\Http\\Model\\Transfer\\Transfer' AND l.branch_id = t.to_branch_id AND l.direction = 'in'
            )");
    }

    public function down(): void
    {
        // Backfilled audit data must not be deleted automatically.
    }
};
