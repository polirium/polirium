<?php

namespace Polirium\Modules\Product\Support;

use Polirium\Modules\Product\Http\Model\Payment\BankAccount;

class VietQrService
{
    public const IMAGE_BASE_URL = 'https://vietqr.app/img';

    /**
     * Build VietQR image URL from bank account + payment details.
     *
     * @see https://vietqr.app
     */
    public static function imageUrl(
        BankAccount|array $account,
        int|float|string|null $amount = null,
        ?string $description = null,
        ?string $template = null,
    ): string {
        $account = is_array($account) ? $account : $account->toArray();

        $params = array_filter([
            'acc' => (string) ($account['account_number'] ?? ''),
            'bank' => (string) ($account['bank_code'] ?? ''),
            'amount' => $amount !== null && $amount !== '' ? (int) round((float) $amount) : null,
            'des' => $description !== null && $description !== '' ? self::sanitizeDescription($description) : null,
            'template' => $template ?: ($account['template'] ?? 'compact') ?: 'compact',
            'showinfo' => ! empty($account['show_info']) ? 'true' : 'false',
            'fullacc' => ! empty($account['full_account']) ? 'true' : 'false',
            'holder' => $account['account_holder'] ?? null,
            'store' => $account['store_name'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        return self::IMAGE_BASE_URL . '?' . http_build_query($params);
    }

    public static function sanitizeDescription(string $description): string
    {
        $description = trim($description);
        // VietQR content should avoid special characters that break QR parsing.
        $description = preg_replace('/[^\p{L}\p{N}\s\-_.,]/u', '', $description) ?: '';

        return mb_substr(trim(preg_replace('/\s+/', ' ', $description) ?: ''), 0, 100);
    }

    /**
     * @return array<int, array{code: string, name: string, short_name: string, bin: string}>
     */
    public static function banks(): array
    {
        return config('modules.product.vietqr_banks', []);
    }

    public static function bankLabel(string $code): string
    {
        foreach (self::banks() as $bank) {
            if (($bank['code'] ?? '') === $code || ($bank['short_name'] ?? '') === $code) {
                return ($bank['short_name'] ?? $bank['code']) . ' - ' . ($bank['name'] ?? '');
            }
        }

        return $code;
    }
}
