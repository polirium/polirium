<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Report;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;

class SalesReportComponent extends Component
{
    public string $datePreset = 'today';

    public string $viewMode = 'sales'; // sales, summary
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $timeFrom = null;

    public ?string $timeTo = null;

    public ?string $customerSearch = null;

    public ?int $userId = null;

    public ?int $authorId = null;

    public ?string $paymentMethod = null;

    public string|int|null $saleChannelId = null;

    public string|int|null $deliveryPartnerId = null;

    public string $status = 'all';

    public function mount(): void
    {
        $this->dateFrom = now()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedDatePreset(string $value): void
    {
        match ($value) {
            'today' => $this->setDateRange(now(), now()),
            'yesterday' => $this->setDateRange(now()->subDay(), now()->subDay()),
            'this_week' => $this->setDateRange(now()->startOfWeek(), now()),
            'last_week' => $this->setDateRange(now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()),
            'this_month' => $this->setDateRange(now()->startOfMonth(), now()),
            'last_month' => $this->setDateRange(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()),
            'this_quarter' => $this->setDateRange(now()->startOfQuarter(), now()),
            'last_quarter' => $this->setDateRange(now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()),
            'this_year' => $this->setDateRange(now()->startOfYear(), now()),
            'last_year' => $this->setDateRange(now()->subYear()->startOfYear(), now()->subYear()->endOfYear()),
            'all_time' => $this->setDateRange(null, null),
            default => null,
        };
    }

    private function setDateRange($from, $to): void
    {
        $this->dateFrom = $from ? $from->format('Y-m-d') : null;
        $this->dateTo = $to ? $to->format('Y-m-d') : null;
    }

    #[Computed]
    public function users(): array
    {
        return \Polirium\Core\Base\Http\Models\User::select('id', 'name')->pluck('name', 'id')->all();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::where('is_active', true)->get();
    }

    #[Computed]
    public function saleChannels(): array
    {
        return SaleChannel::where('is_active', true)->pluck('name', 'id')->all();
    }

    #[Computed]
    public function reportData()
    {
        $query = Payment::query()
            ->with(['customer', 'user', 'author', 'saleChannel', 'branch', 'latestDelivery.partnerDelivery', 'products.product']);

        // Date range
        if ($this->dateFrom) {
            $from = $this->dateFrom . ' ' . ($this->timeFrom ?: '00:00');
            $query->where('created_at', '>=', $from);
        }
        if ($this->dateTo) {
            $to = $this->dateTo . ' ' . ($this->timeTo ?: '23:59:59');
            $query->where('created_at', '<=', $to);
        }

        // Customer search
        if ($this->customerSearch) {
            $search = $this->customerSearch;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // User (seller)
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        // Author (creator)
        if ($this->authorId) {
            $query->where('author_id', $this->authorId);
        }

        // Payment method
        if ($this->paymentMethod) {
            $method = $this->paymentMethod;
            $query->where('type_payment', 'like', "%\"{$method}\"%");
        }

        // Sale channel
        if ($this->saleChannelId !== null && $this->saleChannelId !== '') {
            $channelId = (int) $this->saleChannelId;
            if ($channelId === 0) {
                $query->whereNull('sale_channel_id');
            } else {
                $query->where('sale_channel_id', $channelId);
            }
        }

        // Delivery partner
        if ($this->deliveryPartnerId !== null && $this->deliveryPartnerId !== '') {
            $partnerId = (int) $this->deliveryPartnerId;
            if ($partnerId === 0) {
                $query->whereDoesntHave('latestDelivery', function ($q) {
                    $q->whereNotNull('partner_delivery_id');
                });
            } else {
                $query->whereHas('latestDelivery', function ($q) use ($partnerId) {
                    $q->where('partner_delivery_id', $partnerId);
                });
            }
        }

        // Status
        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    #[Computed]
    public function summary(): array
    {
        $data = $this->reportData->filter(fn ($p) => ! in_array($p->status, ['cancel', 'cancelled', 'failed']));

        $result = [
            'count' => $data->count(),
            'total_cost' => $data->sum('total_cost'),
            'total_discount' => $data->sum(function ($p) {
                if ($p->discount_type === 'percent') {
                    return $p->total_cost * $p->discount_value / 100;
                }

                return $p->discount_value ?? 0;
            }),
            'total_value' => $data->sum('value'),
            'total_payment' => $data->sum('value_payment'),
            'total_products' => $data->sum('amount_products'),
        ];

        if (auth()->user()?->can('accountings.dashboard.cogs')) {
            $result['total_cogs'] = $data->sum(function ($p) {
                return $p->products->sum(function ($item) {
                    return ($item->product?->cost ?? 0) * $item->amount;
                });
            });
        }

        return $result;
    }

    /**
     * Breakdown of revenue by payment method.
     *
     * @return array<string, array{label: string, total: int, count: int}>
     */
    #[Computed]
    public function paymentBreakdown(): array
    {
        // Pre-populate with all active payment methods
        $breakdown = [];
        foreach ($this->paymentMethods as $method) {
            $breakdown[$method->code] = ['label' => $method->name, 'total' => 0, 'count' => 0, 'invoice_ids' => []];
        }

        foreach ($this->reportData as $payment) {
            if (in_array($payment->status, ['cancel', 'cancelled', 'failed'])) {
                continue;
            }

            $methods = $payment->type_payment ?? [];
            if (is_string($methods)) {
                $methods = json_decode($methods, true) ?? [];
            }

            foreach ($methods as $entry) {
                $code = $entry['method'] ?? 'unknown';
                $label = $entry['label'] ?? $code;
                $value = (int) ($entry['value'] ?? 0);

                if (! isset($breakdown[$code])) {
                    $breakdown[$code] = ['label' => $label, 'total' => 0, 'count' => 0, 'invoice_ids' => []];
                }
                $breakdown[$code]['total'] += $value;

                if (! in_array($payment->id, $breakdown[$code]['invoice_ids'])) {
                    $breakdown[$code]['invoice_ids'][] = $payment->id;
                    $breakdown[$code]['count']++;
                }
            }
        }

        return array_map(function ($item) {
            unset($item['invoice_ids']);

            return $item;
        }, $breakdown);
    }

    /**
     * Breakdown by invoice status for reconciliation.
     *
     * @return array<string, array{count: int, total: int}>
     */
    #[Computed]
    public function statusBreakdown(): array
    {
        // Pre-populate with all known statuses
        $labels = [
            'success' => 'Hoàn thành',
            'cancel' => 'Đã hủy',
            'delivery_failed' => 'Không giao được',
        ];

        $breakdown = [];
        foreach ($labels as $key => $label) {
            $breakdown[$key] = ['label' => $label, 'count' => 0, 'total' => 0, 'payment' => 0];
        }

        foreach ($this->reportData as $payment) {
            $status = $payment->status ?? 'unknown';
            if (! isset($breakdown[$status])) {
                $breakdown[$status] = ['label' => $labels[$status] ?? $status, 'count' => 0, 'total' => 0, 'payment' => 0];
            }
            $breakdown[$status]['count']++;
            $breakdown[$status]['total'] += $payment->value ?? 0;
            $breakdown[$status]['payment'] += $payment->value_payment ?? 0;
        }

        return $breakdown;
    }

    /**
     * Breakdown by sale channel.
     *
     * @return array<int, array{name: string, count: int, total: int}>
     */
    #[Computed]
    public function channelBreakdown(): array
    {
        // Pre-populate with all known channels
        $breakdown = [];
        foreach ($this->saleChannels as $id => $name) {
            $breakdown[$id] = ['name' => $name, 'count' => 0, 'total' => 0];
        }

        foreach ($this->reportData as $payment) {
            if (in_array($payment->status, ['cancel', 'cancelled', 'failed'])) {
                continue;
            }

            $channelId = $payment->sale_channel_id ?? 0;
            $channelName = $payment->saleChannel?->name ?? 'Không xác định';

            if (! isset($breakdown[$channelId])) {
                $breakdown[$channelId] = ['name' => $channelName, 'count' => 0, 'total' => 0];
            }
            $breakdown[$channelId]['count']++;
            $breakdown[$channelId]['total'] += $payment->value_payment ?? 0;
        }

        return $breakdown;
    }

    /**
     * Breakdown by delivery partner.
     *
     * @return array<int, array{name: string, count: int, total: int}>
     */
    #[Computed]
    public function deliveryPartnerBreakdown(): array
    {
        // Pre-populate with all known delivery partners
        $breakdown = [];
        $partners = \Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery::where('is_active', true)->pluck('name', 'id')->all();
        foreach ($partners as $id => $name) {
            $breakdown[$id] = ['name' => $name, 'count' => 0, 'total' => 0];
        }
        // Also add "Không có" for invoices without delivery partner
        $breakdown[0] = ['name' => 'Không có', 'count' => 0, 'total' => 0];

        foreach ($this->reportData as $payment) {
            if (in_array($payment->status, ['cancel', 'cancelled', 'failed'])) {
                continue;
            }

            $partner = $payment->latestDelivery?->partnerDelivery;
            $partnerId = $partner?->id ?? 0;
            $partnerName = $partner?->name ?? 'Không có';

            if (! isset($breakdown[$partnerId])) {
                $breakdown[$partnerId] = ['name' => $partnerName, 'count' => 0, 'total' => 0];
            }
            $breakdown[$partnerId]['count']++;
            $breakdown[$partnerId]['total'] += $payment->value_payment ?? 0;
        }

        return $breakdown;
    }

    /**
     * Breakdown by author (creator).
     *
     * @return array<int, array{name: string, count: int, total: int}>
     */
    #[Computed]
    public function authorBreakdown(): array
    {
        $breakdown = [];

        foreach ($this->reportData as $payment) {
            if (in_array($payment->status, ['cancel', 'cancelled', 'failed'])) {
                continue;
            }

            $authorId = $payment->author_id ?? 0;
            $authorName = $payment->author?->name ?? 'Không xác định';

            if (! isset($breakdown[$authorId])) {
                $breakdown[$authorId] = ['name' => $authorName, 'count' => 0, 'total' => 0];
            }
            $breakdown[$authorId]['count']++;
            $breakdown[$authorId]['total'] += $payment->value_payment ?? 0;
        }

        return $breakdown;
    }

    public function clearBreakdownFilters(): void
    {
        $this->paymentMethod = null;
        $this->status = 'all';
        $this->saleChannelId = null;
        $this->authorId = null;
        $this->deliveryPartnerId = null;
    }

    public function toggleChannelFilter(int $channelId): void
    {
        if ($this->saleChannelId !== null && (int) $this->saleChannelId === $channelId) {
            $this->saleChannelId = null;
        } else {
            $this->saleChannelId = $channelId;
        }
    }

    public function toggleDeliveryPartnerFilter(int $partnerId): void
    {
        if ($this->deliveryPartnerId !== null && (int) $this->deliveryPartnerId === $partnerId) {
            $this->deliveryPartnerId = null;
        } else {
            $this->deliveryPartnerId = $partnerId;
        }
    }

    public function render()
    {
        return view('modules/accounting::report.report-view');
    }
}
