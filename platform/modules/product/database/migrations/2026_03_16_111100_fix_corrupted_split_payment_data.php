<?php

use Illuminate\Database\Migrations\Migration;
use Polirium\Modules\Accounting\Http\Model\AccountingType;
use Polirium\Modules\Accounting\Http\Model\Receipt;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

/**
 * Fix corrupted type_payment data caused by split-payment bug.
 *
 * Bug: PaymentComponent::updatedPayment() was overwriting type_payment[0].value
 * with the TOTAL value_payment when multiple payment methods were used.
 * This caused the first method's amount to be inflated to equal the total.
 *
 * Additionally fixes corresponding Receipt (Phiếu Thu) records that were
 * created with the corrupted values.
 *
 * Recovery logic:
 * 1. For payments with 2+ type_payment items where sum != value_payment,
 *    correct first item value = value_payment - sum(other items)
 * 2. For receipts linked to those payments, match by AccountingType name
 *    and update the cash receipt value accordingly
 */
return new class () extends Migration {
    public function up(): void
    {
        $fixedPayments = 0;
        $fixedReceipts = 0;
        $skipped = 0;

        // Get AccountingType IDs for matching receipts to payment methods
        $cashTypeId = AccountingType::where('name', 'Thu tiền mặt')->value('id');
        $bankTypeId = AccountingType::where('name', 'Thu chuyển khoản')->value('id');

        Payment::query()
            ->whereNotNull('type_payment')
            ->where('value_payment', '>', 0)
            ->chunk(100, function ($payments) use (&$fixedPayments, &$fixedReceipts, &$skipped, $cashTypeId, $bankTypeId) {
                foreach ($payments as $payment) {
                    $typePayment = $payment->type_payment;

                    // Skip non-array or single-method payments (not affected by bug)
                    if (! is_array($typePayment) || count($typePayment) <= 1) {
                        continue;
                    }

                    $totalInMethods = collect($typePayment)->sum('value');

                    // If sum already matches value_payment, data is correct
                    if ((int) $totalInMethods === (int) $payment->value_payment) {
                        $skipped++;

                        continue;
                    }

                    // === FIX PAYMENT type_payment ===
                    // First item was corrupted to equal total. Correct = total - sum(others)
                    $sumOtherMethods = 0;
                    for ($i = 1; $i < count($typePayment); $i++) {
                        $sumOtherMethods += (int) ($typePayment[$i]['value'] ?? 0);
                    }

                    $correctFirstValue = (int) $payment->value_payment - $sumOtherMethods;

                    if ($correctFirstValue < 0) {
                        echo "⚠️  Payment #{$payment->id} ({$payment->code}): cannot auto-fix (negative). Manual review needed.\n";
                        $skipped++;

                        continue;
                    }

                    $oldFirstValue = (int) ($typePayment[0]['value'] ?? 0);
                    $firstMethod = $typePayment[0]['method'] ?? 'cash';
                    $methodName = $typePayment[0]['label'] ?? $firstMethod;

                    $typePayment[0]['value'] = $correctFirstValue;
                    $payment->type_payment = $typePayment;
                    $payment->saveQuietly();
                    $fixedPayments++;

                    echo "✅ Payment #{$payment->id} ({$payment->code}): {$methodName} {$oldFirstValue} → {$correctFirstValue}\n";

                    // === FIX RECEIPTS (Phiếu Thu) ===
                    // Find the receipt for the first (corrupted) payment method
                    $firstMethodTypeId = match ($firstMethod) {
                        'cash' => $cashTypeId,
                        'bank', 'transfer' => $bankTypeId,
                        default => null,
                    };

                    if ($firstMethodTypeId) {
                        $receipt = Receipt::where('finance_type', Payment::class)
                            ->where('finance_id', $payment->id)
                            ->where('type_id', $firstMethodTypeId)
                            ->where('value', $oldFirstValue)
                            ->first();

                        if ($receipt) {
                            $receipt->value = $correctFirstValue;
                            $receipt->saveQuietly();
                            $fixedReceipts++;
                            echo "   📝 Receipt #{$receipt->id} ({$receipt->code}): {$oldFirstValue} → {$correctFirstValue}\n";
                        }
                    }
                }
            });

        echo "\n📊 Summary:\n";
        echo "   Payments fixed: {$fixedPayments}\n";
        echo "   Receipts fixed: {$fixedReceipts}\n";
        echo "   Skipped (correct): {$skipped}\n";
    }

    public function down(): void
    {
        // Cannot reverse - original corrupted data is not recoverable
    }
};
