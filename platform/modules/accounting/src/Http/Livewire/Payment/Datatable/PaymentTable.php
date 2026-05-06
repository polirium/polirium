<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;

final class PaymentTable extends BaseTable
{
    public string $tableName = 'product-payment-table';

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-product-payments' => '$refresh',
                'datatable-payment-filter' => 'handleFilter',
                'datatable-payment-filter-clear' => 'clearFilter',
            ]
        );
    }

    public int|string|null $targetId = null;

    public $customerId = null;
    public bool $compactMode = false;

    public function setTargetId($id): void
    {
        $this->targetId = $id;
    }

    public function commitComplete(): void
    {
        if (! auth()->user()->can('accountings.edit')) {
            $this->dispatch('error', 'Bạn không có quyền cập nhật hóa đơn.');

            return;
        }

        if ($this->targetId) {
            $this->complete($this->targetId);
            $this->targetId = null;
        }
    }

    public function commitCancel(): void
    {
        if (! auth()->user()->can('accountings.cancel')) {
            $this->dispatch('error', 'Bạn không có quyền hủy hóa đơn.');

            return;
        }

        if ($this->targetId) {
            $this->cancel($this->targetId);
            $this->targetId = null;
        }
    }

    public function commitDelete(): void
    {
        if (! auth()->user()->can('sales.orders.delete')) {
            $this->dispatch('error', 'Bạn không có quyền xóa hóa đơn.');

            return;
        }

        if ($this->targetId) {
            $this->delete($this->targetId);
            $this->targetId = null;
        }
    }

    protected int $tab = 1;

    public function mount(): void
    {
        parent::mount();

        if ($this->compactMode || $this->customerId !== null) {
            return;
        }

        if (($this->filters['date'] ?? '') === '' && ! request()->query('search')) {
            $this->filters['date'] = Carbon::today()->toDateString();
        }
    }

    public function boot(): void
    {
        $searchParam = request()->query('search');
        if ($searchParam && ! $this->search) {
            $this->search = $searchParam;
        }
    }

    public function setUp(): array
    {
        if (! $this->compactMode) {
            $this->showCheckBox();
            $this->enableFilterBuilder();
        }

        $setup = [
            PowerGrid::detail()
                ->view('modules/accounting::payment.datatable.detail')
                ->options(['full-page' => true])
                ->showCollapseIcon(),
        ];

        if (! $this->compactMode) {
            $setup[] = PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV);
            $setup[] = PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/accounting::payment.datatable.header');
            $setup[] = PowerGrid::footer()->showPerPage()->showRecordCount();
        } else {
            $setup[] = PowerGrid::footer()->showPerPage(5);
        }

        return $setup;
    }

    public string $bulkDeletePermission = 'sales.orders.delete';

    public function delete(string|int $id): void
    {
        if (! auth()->user()->can('sales.orders.delete')) {
            $this->dispatch('error', 'Bạn không có quyền xóa hóa đơn.');

            return;
        }

        $item = Payment::find($id);
        if (! $item) {
            return;
        }

        // Lấy danh sách product_id bị ảnh hưởng trước khi xóa logs
        $affectedProductIds = \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Payment::class)
            ->where('productable_id', $item->id)
            ->pluck('product_id')
            ->unique();

        // Xóa tất cả product_logs liên quan đến payment này
        \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Payment::class)
            ->where('productable_id', $item->id)
            ->delete();

        // Xóa product_refunds liên quan
        \DB::table('product_refunds')
            ->where('payment_id', $item->id)
            ->delete();

        // Recalculate inventory cho các sản phẩm bị ảnh hưởng
        foreach ($affectedProductIds as $productId) {
            $this->recalculateProductInventory($productId);
        }

        $item->delete();
    }

    /**
     * Tính lại toàn bộ chuỗi tồn kho cho 1 sản phẩm sau khi xóa logs.
     */
    private function recalculateProductInventory(int $productId): void
    {
        $logs = \Polirium\Modules\Product\Http\Model\ProductLog::where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $runningQty = 0;
        foreach ($logs as $log) {
            $before = $runningQty;
            $delta = $log->amount_after - $log->amount_before; // giữ nguyên dấu gốc
            $runningQty = $before + $delta;

            if ($log->amount_before !== $before || $log->amount_after !== $runningQty) {
                $log->update([
                    'amount_before' => $before,
                    'amount_after' => $runningQty,
                ]);
            }
        }

        // Sync products.qty và product_branches.qty
        \Polirium\Modules\Product\Http\Model\Product::where('id', $productId)->update(['qty' => $runningQty]);
        \DB::table('product_branches')->where('product_id', $productId)->update(['qty' => $runningQty]);
    }

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    public function datasource(): Builder
    {
        return $this->filterQuery()
            ->selectRaw('product_payments.*, (product_payments.value - product_payments.value_payment) as remaining_amount')
            ->with(['customer', 'products.product', 'user', 'author', 'saleChannel', 'refunds.user', 'latestDelivery.partnerDelivery']);
    }

    public function bulkComplete(): void
    {
        if (empty($this->checkboxValues)) {
            $this->dispatch('error', trans('modules/accounting::accounting.select_at_least_one'));

            return;
        }

        if (! auth()->user()->can('accountings.edit')) {
            $this->dispatch('error', trans('modules/accounting::accounting.no_permission_update'));

            return;
        }

        $count = 0;
        foreach ($this->checkboxValues as $id) {
            $this->complete($id);
            $count++;
        }

        $this->checkboxValues = [];

        $this->dispatch('success', trans('Đã hoàn thành :count hóa đơn.', ['count' => $count]));
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
    }

    public function exportOverview()
    {
        if (! auth()->user()?->can('accountings.export')) {
            $this->dispatch('error', 'Bạn không có quyền xuất file hóa đơn.');

            return;
        }

        $export = new \Polirium\Modules\Accounting\Exports\InvoiceOverviewExport($this->filters);

        return $export->download('HoaDon_TongQuan_' . now()->format('dmY_His') . '.xlsx');
    }

    public function exportDetail()
    {
        if (! auth()->user()?->can('accountings.export')) {
            $this->dispatch('error', 'Bạn không có quyền xuất file hóa đơn.');

            return;
        }

        $export = new \Polirium\Modules\Accounting\Exports\InvoiceDetailExport($this->filters);

        return $export->download('HoaDon_ChiTiet_' . now()->format('dmY_His') . '.xlsx');
    }

    public function bulkCancel(): void
    {
        if (empty($this->checkboxValues)) {
            $this->dispatch('error', trans('modules/accounting::accounting.select_at_least_one'));

            return;
        }

        if (! auth()->user()->can('accountings.cancel')) {
            $this->dispatch('error', trans('modules/accounting::accounting.no_permission_cancel'));

            return;
        }

        $count = 0;
        foreach ($this->checkboxValues as $id) {
            $this->cancel($id);
            $count++;
        }

        $this->checkboxValues = [];

        $this->dispatch('success', trans('Đã hủy :count hóa đơn.', ['count' => $count]));
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
    }

    /**
     * Lấy danh sách mã phương thức thanh toán "thu tiền ngay" từ DB
     * (target_payment_status = 'completed')
     */
    private function getCollectedMethodCodes(): array
    {
        static $codes = null;

        if ($codes === null) {
            $codes = \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::query()
                ->where('target_payment_status', PaymentMethod::STATUS_COMPLETED)
                ->pluck('code')
                ->toArray();
        }

        return $codes;
    }

    protected function filterQuery(): Builder
    {
        return Payment::query()
            ->when(user_branch(), function ($q) {
                $q->where('branch_id', user_branch());
            })
            ->when(! empty($this->search) || ! empty($this->filters['code']), function ($q) {
                // Ignore status filters entirely if specifically searching by code/global
            }, function ($filterQ) {
                $filterQ->when(! empty($this->filters['status_checked']), function ($q) {
                    $q->whereIn('status', $this->filters['status_checked']);
                }, function ($q) {
                    $q->whereNotIn('status', ['cancelled', 'cancel', 'failed', 'delivery_failed']);
                });
            })
            ->when($this->filters['code'] ?? null, function ($q, $code) {
                $q->where('product_payments.code', 'like', '%' . $code . '%');
            })
            ->when($this->filters['user_id'] ?? null, function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->when($this->filters['sale_channel_id'] ?? null, function ($q, $channelId) {
                $q->where('sale_channel_id', $channelId);
            })
            ->when($this->filters['customer_id'] ?? null, function ($q, $customerId) {
                $q->where('customer_id', $customerId);
            })
            ->when($this->customerId, function ($q) {
                $q->where('customer_id', $this->customerId);
            })
            ->when($this->filters['order_type'] ?? null, function ($q, $orderType) {
                if ($orderType === 'collected') {
                    // Đã thu tiền: value_payment >= value (thanh toán đủ)
                    $q->whereRaw('value_payment >= value');
                } elseif ($orderType === 'pending') {
                    // Còn cần thu: value_payment < value (chưa thanh toán đủ)
                    $q->whereRaw('value_payment < value');
                }
            })
            ->when($this->filters['delivery_partner_id'] ?? null, function ($q, $partnerId) {
                $q->whereHas('latestDelivery', function ($query) use ($partnerId) {
                    $query->where('partner_delivery_id', $partnerId);
                });
            })
            ->when($this->filters['type_payment_method'] ?? null, function ($q, $method) {
                $q->whereJsonContains('type_payment', [['method' => $method]]);
            })
            ->when($this->filters['date'] ?? null, function ($q, $date) {
                $dates = explode(' to ', $date);
                if (count($dates) === 2) {
                    $q->whereBetween('product_payments.created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
                } else {
                    $q->whereDate('product_payments.created_at', $date);
                }
            })
            ->when($this->filters['product_search'] ?? null, function ($q, $search) {
                $q->whereHas('products.product', function ($sub) use ($search) {
                    $sub->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            });
    }

    public function getTotalsProperty()
    {
        $query = $this->filterQuery();

        $totals = $query->selectRaw('
            SUM(total_cost) as total_cost,
            SUM(CASE WHEN discount_type = \'percent\' THEN total_cost * discount_value / 100 ELSE discount_value END) as discount_value,
            SUM(value) as total_need_pay,
            SUM(value_payment) as total_paid
        ')->first();

        $remaining = ($totals->total_need_pay ?? 0) - ($totals->total_paid ?? 0);

        $result = [
            'total_cost' => $totals->total_cost ?? 0,
            'discount_value' => $totals->discount_value ?? 0,
            'total_need_pay' => $totals->total_need_pay ?? 0,
            'total_paid' => $totals->total_paid ?? 0,
            'total_remaining' => $remaining > 0 ? $remaining : 0,
        ];

        if (auth()->user()?->can('accountings.dashboard.cogs')) {
            $cogsQuery = $this->filterQuery();
            $totalCogs = $cogsQuery
                ->join('product_payment_products', 'product_payments.id', '=', 'product_payment_products.product_payment_id')
                ->join('products', 'product_payment_products.product_id', '=', 'products.id')
                ->selectRaw('SUM(products.cost * product_payment_products.amount) as total_cogs')
                ->value('total_cogs');

            $result['total_cogs'] = $totalCogs ?? 0;
        }

        return $result;
    }

    public function relationSearch(): array
    {
        return [
            'customer' => ['name', 'phone'],
        ];
    }

    /**
     * Kiểm tra xem đơn hàng chỉ dùng phương thức thanh toán "thu tiền ngay" hay không
     * Dựa trên target_payment_status = 'completed' trong bảng payment_methods
     */
    private function hasOnlyCollectedMethods(Payment $payment): bool
    {
        $types = is_array($payment->type_payment) ? $payment->type_payment : [];
        $collectedCodes = $this->getCollectedMethodCodes();

        foreach ($types as $type) {
            if (! in_array($type['method'] ?? '', $collectedCodes)) {
                return false;
            }
        }

        return count($types) > 0;
    }

    public function fields(): PowerGridFields
    {
        $methodsMap = \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::pluck('name', 'code')->toArray();

        return PowerGrid::fields()
            // 1. Mã hóa đơn (code - built-in)

            // 1.5. Nhân viên tạo
            ->add('author_name', function (Payment $payment) {
                return $payment->author?->name ?? ($payment->user?->name ?? '-');
            })

            // 2. Ngày tạo
            ->add('created_at_formatted', function (Payment $payment) {
                return $payment->created_at ? $payment->created_at->format('d/m H:i') : '';
            })

            // 3. Ngày hoàn thành (dùng cột completed_at riêng)
            ->add('completed_at_formatted', function (Payment $payment) {
                if ($payment->completed_at) {
                    return $payment->completed_at->format('d/m H:i');
                }

                return '<span class="text-muted">-</span>';
            })

            // 4. Khách hàng
            ->add('customer_name', function (Payment $payment) {
                return $payment->customer?->name ?? 'Khách lẻ';
            })

            // 5. SĐT
            ->add('customer_phone', function (Payment $payment) {
                return $payment->customer?->phone ?? '-';
            })

            // 6. Địa chỉ (chữ nhỏ, tự xuống dòng)
            ->add('customer_address', function (Payment $payment) {
                $address = $payment->customer?->address;
                if (! $address) {
                    return '<span class="text-muted">-</span>';
                }

                return '<span class="small" style="white-space: normal; word-break: break-word; max-width: 150px; display: inline-block;">'
                    . e($address) . '</span>';
            })

            // 7. Kênh bán
            ->add('sale_channel_name', function (Payment $payment) {
                return $payment->saleChannel?->name ?? '-';
            })

            // 8. Đối tác GH
            ->add('delivery_partner_name', function (Payment $payment) {
                return $payment->latestDelivery?->partnerDelivery?->name ?? '-';
            })

            // 9. Mã vận đơn
            ->add('delivery_code', function (Payment $payment) {
                return $payment->latestDelivery?->code ?? '-';
            })

            // 10a. Phương thức thanh toán (Tên)
            ->add('payment_method_names', function (Payment $payment) use ($methodsMap) {
                $types = is_array($payment->type_payment) ? $payment->type_payment : [];
                $html = [];
                foreach ($types as $type) {
                    $method = $type['method'] ?? '';
                    $label = $methodsMap[$method] ?? $type['label'] ?? (($method === 'cash') ? 'Tiền mặt' : (($method === 'bank') ? 'Chuyển khoản' : ucfirst($method)));

                    $colorClass = match ($method) {
                        'cash' => 'text-success',
                        'bank' => 'text-primary',
                        'card' => 'text-info',
                        'cod' => 'text-cyan',
                        'other' => 'text-purple',
                        'e-wallet', 'momo' => 'text-warning',
                        'installment' => 'text-indigo',
                        'vnpay' => 'text-red',
                        default => 'text-secondary',
                    };

                    $html[] = "<div class='mb-1 fw-medium {$colorClass}'>{$label}</div>";
                }

                return count($html) > 0 ? implode('', $html) : '<span class="text-muted">-</span>';
            })

            // 10b. Phương thức thanh toán (Số tiền)
            ->add('payment_method_amounts', function (Payment $payment) {
                $types = is_array($payment->type_payment) ? $payment->type_payment : [];
                $html = [];
                foreach ($types as $type) {
                    $value = core_number_format($type['value'] ?? 0);
                    $html[] = "<div class='mb-1 text-end'>{$value}</div>";
                }

                return count($html) > 0 ? implode('', $html) : '<span class="text-muted">-</span>';
            })

            // 11. Ghi chú
            ->add('note_formatted', function (Payment $payment) {
                return $payment->note;
            })

            // 12. Khách Cần Trả (= value = total_cost - discount)
            ->add('value_formatted', function (Payment $payment) {
                return core_number_format($payment->value);
            })

            // 13. Khách Đã Trả (= value_payment)
            ->add('value_payment_formatted', function (Payment $payment) {
                return core_number_format($payment->value_payment);
            })

            // 14. Còn Cần Thu (= value - value_payment)
            ->add('remaining_amount_formatted', function (Payment $payment) {
                $remaining = $payment->value - $payment->value_payment;
                if ($remaining > 0) {
                    return '<span class="text-danger fw-bold">' . core_number_format($remaining) . '</span>';
                }

                return '<span class="text-muted">0</span>';
            })

            // 15. Trạng thái
            ->add('status_name', function (Payment $payment) {
                // COD/Other chưa thu đủ tiền → Đang xử lý
                $remaining = $payment->value - $payment->value_payment;
                if ($remaining > 0 && $payment->status === 'success' && ! $this->hasOnlyCollectedMethods($payment)) {
                    return '<span class="badge bg-warning-lt text-warning">Đang xử lý</span>';
                }

                return match ($payment->status) {
                    'success' => '<span class="badge bg-success-lt text-success">Hoàn thành</span>',
                    'pending' => '<span class="badge bg-warning-lt text-warning">Chờ xử lý</span>',
                    'draft' => '<span class="badge bg-warning-lt text-warning">Tạm</span>',
                    'cancel' => '<span class="badge bg-danger-lt text-danger">Đã hủy</span>',
                    'delivery_failed' => '<span class="badge bg-danger-lt text-danger">Không giao được</span>',
                    default => '<span class="badge bg-muted-lt text-muted">' . $payment->status . '</span>',
                };
            })

            // 16. Tổng Tiền (chưa giảm giá = total_cost)
            ->add('total_cost_formatted', function (Payment $payment) {
                return core_number_format($payment->total_cost);
            })

            // 16.5. Giá vốn (cost of goods sold)
            ->add('cogs_formatted', function (Payment $payment) {
                $totalCogs = $payment->products->sum(function ($item) {
                    return ($item->product?->cost ?? 0) * $item->amount;
                });

                return core_number_format($totalCogs);
            })

            // 17. Giảm Giá
            ->add('discount_value_formatted', function (Payment $payment) {
                if ($payment->discount_value <= 0) {
                    return '<span class="text-muted">0</span>';
                }

                $amount = $payment->discount_type === 'percent'
                    ? $payment->total_cost * $payment->discount_value / 100
                    : $payment->discount_value;

                $label = core_number_format($amount);

                if ($payment->discount_type === 'percent') {
                    $label .= ' <span class="text-muted small">(' . $payment->discount_value . '%)</span>';
                }

                return $label;
            })

            // Actions
            ->add('actions_view', function (Payment $payment) {
                return view('modules/accounting::payment.datatable.actions', [
                    'row' => $payment,
                    'shouldShowCompleteButton' => $this->shouldShowCompleteButton($payment),
                ])->render();
            });
    }

    public function columns(): array
    {
        if ($this->compactMode) {
            return [
                Column::make('Mã hóa đơn', 'code')->sortable()->searchable(),
                Column::add()->title(trans('modules/accounting::accounting.created_at'))->field('created_at_formatted', 'created_at')->sortable(),
                Column::add()->title(trans('modules/accounting::accounting.customer_refundable'))->field('value_formatted', 'value')->sortable(),
                Column::add()->title(trans('modules/accounting::accounting.customer_paid'))->field('value_payment_formatted', 'value_payment')->sortable(),
                Column::add()->title(trans('modules/accounting::accounting.status'))->field('status_name'),
                Column::make(trans('core/base::general.action'), 'actions_view')->visibleInExport(false),
            ];
        }

        return [
            // 1. Mã hóa đơn
            Column::make('Mã hóa đơn', 'code')
                ->sortable()
                ->searchable(),

            // 1.5. Nhân viên tạo
            Column::add()
                ->title(trans('modules/accounting::accounting.staff'))
                ->field('author_name')
                ->searchable(),

            // 2. Ngày tạo
            Column::add()
                ->title(trans('modules/accounting::accounting.created_at'))
                ->field('created_at_formatted', 'created_at')
                ->sortable(),

            // 3. Ngày hoàn thành
            Column::add()
                ->title(trans('modules/accounting::accounting.completed_date'))
                ->field('completed_at_formatted')
                ->hidden(false, false),

            // 4. Khách hàng
            Column::add()
                ->title(trans('modules/accounting::accounting.customer'))
                ->field('customer_name')
                ->searchable(),

            // 5. SĐT
            Column::add()
                ->title(trans('modules/accounting::accounting.phone'))
                ->field('customer_phone')
                ->searchable(),

            // 6. Địa chỉ
            Column::add()
                ->title(trans('modules/accounting::accounting.address'))
                ->field('customer_address')
                ->bodyAttribute('style', 'min-width: 130px; max-width: 180px; white-space: normal; line-height: 1.5;'),

            // 7. Kênh bán
            Column::add()
                ->title(trans('Kênh bán'))
                ->field('sale_channel_name'),

            // 9. Đối tác GH
            Column::add()
                ->title(trans('modules/accounting::accounting.delivery_partner_short'))
                ->field('delivery_partner_name'),

            // 10. Mã vận đơn
            Column::add()
                ->title(trans('modules/accounting::accounting.delivery_code'))
                ->field('delivery_code'),

            // 11. Ghi chú
            Column::make('Ghi chú', 'note')
                ->field('note_formatted', 'note')
                ->searchable()
                ->bodyAttribute('style', 'min-width: 200px; max-width: 250px; white-space: normal; line-height: 1.5;'),

            // 12. Phương thức TT
            Column::add()
                ->title(trans('modules/accounting::accounting.payment_method_short'))
                ->field('payment_method_names'),

            // 12. Số tiền TT (theo phương thức)
            Column::add()
                ->title(trans('modules/accounting::accounting.amount'))
                ->field('payment_method_amounts')
                ->bodyAttribute('class', 'text-end'),

            // 13. Khách Cần Trả (= value)
            Column::add()
                ->title(trans('modules/accounting::accounting.customer_refundable'))
                ->field('value_formatted', 'value')
                ->sortable(),

            // 14. Khách Đã Trả (= value_payment)
            Column::add()
                ->title(trans('modules/accounting::accounting.customer_paid'))
                ->field('value_payment_formatted', 'value_payment')
                ->sortable(),

            // 15. Còn Cần Thu
            Column::add()
                ->title(trans('modules/accounting::accounting.remaining_receivable'))
                ->field('remaining_amount_formatted', 'remaining_amount')
                ->sortable(),

            // 16. Trạng thái
            Column::add()
                ->title(trans('modules/accounting::accounting.status'))
                ->field('status_name'),

            // 17. Tổng Tiền (chưa giảm giá)
            Column::add()
                ->title(trans('modules/accounting::accounting.total_amount'))
                ->field('total_cost_formatted', 'total_cost')
                ->sortable(),

            // 17.5. Giá vốn (permission-gated)
            ...(auth()->user()?->can('accountings.dashboard.cogs') ? [
                Column::add()
                    ->title(trans('modules/accounting::accounting.cost_price'))
                    ->field('cogs_formatted'),
            ] : []),

            // 18. Giảm Giá
            Column::add()
                ->title(trans('modules/accounting::accounting.discount'))
                ->field('discount_value_formatted')
                ->hidden(true, false),

            // Actions
            Column::make(trans('core/base::general.action'), 'actions_view')
                ->visibleInExport(false),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    /**
     * Determine if we should show the Complete button for this payment
     * Show for any order not yet fully completed (completed_at = null)
     */
    protected function shouldShowCompleteButton(Payment $row): bool
    {
        // Đã hoàn thành rồi → ẩn nút
        if ($row->completed_at !== null) {
            return false;
        }

        // Cancelled → ẩn nút
        if (in_array($row->status, ['cancelled', 'cancel', 'delivery_failed'])) {
            return false;
        }

        return true;
    }

    public function complete($id)
    {
        if (! auth()->user()->can('accountings.edit')) {
            $this->dispatch('error', 'Bạn không có quyền cập nhật hóa đơn.');

            return;
        }

        $payment = Payment::find($id);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn.');

            return;
        }

        if (! $this->shouldShowCompleteButton($payment)) {
            $this->dispatch('error', 'Không thể hoàn thành đơn hàng này.');

            return;
        }

        // Hoàn thành = status success + thu tiền đủ + đánh dấu ngày hoàn thành
        $payment->status = 'success';
        $payment->value_payment = $payment->value;
        $payment->completed_at = now();
        $payment->save();

        $this->dispatch('success', 'Đã hoàn thành đơn hàng.');
    }

    public function cancel($id)
    {
        if (! auth()->user()->can('accountings.cancel')) {
            $this->dispatch('error', 'Bạn không có quyền hủy hóa đơn.');

            return;
        }

        $payment = Payment::with('products')->find($id);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn.');

            return;
        }

        if (in_array($payment->status, ['cancelled', 'cancel', 'failed'])) {
            $this->dispatch('error', 'Hóa đơn đã bị hủy trước đó.');

            return;
        }

        // Lấy danh sách product_id bị ảnh hưởng
        $affectedProductIds = \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Payment::class)
            ->where('productable_id', $payment->id)
            ->pluck('product_id')
            ->unique();

        // Xóa tất cả product_logs liên quan đến payment này (net = 0)
        \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Payment::class)
            ->where('productable_id', $payment->id)
            ->delete();

        // Recalculate inventory cho các sản phẩm bị ảnh hưởng
        foreach ($affectedProductIds as $productId) {
            $this->recalculateProductInventory($productId);
        }

        $payment->status = 'cancel';
        $payment->save();

        if ($payment->finance) {
            $payment->finance->status = 'cancelled';
            $payment->finance->save();
        }

        $this->dispatch('success', 'Đã hủy hóa đơn và hoàn lại tồn kho.');
    }

    public array $filters = [
        'code' => '',
        'status' => '',
        'status_checked' => [],
        'user_id' => '',
        'date' => '',
        'sale_channel_id' => '',
        'customer_id' => '',
        'order_type' => '',
        'delivery_partner_id' => '',
        'type_payment_method' => '',
        'product_search' => '',
    ];

    public function handleFilter($value, $key): void
    {
        $this->filters[$key] = $value;
        $this->resetPage();
    }

    public function clearFilter(string $field = ''): void
    {
        $this->filters = [
            'code' => '',
            'status' => '',
            'status_checked' => [],
            'user_id' => '',
            'date' => '',
            'sale_channel_id' => '',
            'customer_id' => '',
            'order_type' => '',
            'delivery_partner_id' => '',
            'type_payment_method' => '',
            'product_search' => '',
        ];
        $this->resetPage();
    }
}
