<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Polirium\Modules\Product\Http\Model\Category;
use Polirium\Modules\Product\Http\Model\Product;

class PaymentV2Component extends PaymentComponent
{
    public int $filterCategoryId = 0;
    public string $searchProduct = '';

    public function mount($tab_selected = null)
    {
        parent::mount($tab_selected);

        \Log::info("V2 MOUNT: Tab={$tab_selected} | Key={$this->session_key}");
        if (session()->has($this->session_key)) {
            \Log::info('V2 SESSION FOUND: Products Count = ' . count($this->products));
        } else {
            \Log::info('V2 SESSION MISSING. Resetting inputs.');
        }

        // Ensure user_id is set to current user if empty (Auto-select fix)
        if (empty($this->payment['user_id']) && auth()->check()) {
            $this->payment['user_id'] = auth()->id();
            $this->payment_user_id = auth()->id(); // Sync separate property
        }

        // Ensure current user is in the list (Double check for V2)
        if (auth()->check() && ! isset($this->lists['users'][auth()->id()])) {
            $this->lists['users'][auth()->id()] = auth()->user()->name;
        }

        // Trigger save to persist this auto-selection
        $this->saveStateToSession();

        // Fix Auto-Select UI: Force TomSelect to update
        $this->dispatch("update-payment-user-{$this->tab_selected}", value: $this->payment['user_id']);
    }

    public function render()
    {
        // Copy logic from parent internal render if needed, or just prepare data
        // Parent render() does user list formatting. We should probably replicate that or call parent::render() if it didn't return a view.
        // But parent::render() returns a view directly.
        // So we must duplicate the user list formatting logic here to ensure it works for V2 view.

        if (isset($this->lists['users'])) {
            $formattedUsers = [];
            $currentUserId = $this->payment['user_id'] ?? null;

            foreach ($this->lists['users'] as $key => $val) {
                // Determine ID and Name whether input is array or simple key-value
                // If it's already formatted (re-render), handle that.
                if (is_array($val) && isset($val['id']) && isset($val['name'])) {
                    $id = $val['id'];
                    $name = $val['name'];
                    // Re-evaluate selected status
                    $selected = ((int)$id === (int)$currentUserId);
                    $val['selected'] = $selected;
                    $formattedUsers[] = $val;
                } else {
                    $id = is_array($val) ? ($val['id'] ?? $key) : $key;
                    $name = is_array($val) ? ($val['name'] ?? $val['label'] ?? '') : $val;

                    $selected = ((int)$id === (int)$currentUserId);
                    $formattedUsers[] = [
                        'id' => $id,
                        'name' => $name,
                        'selected' => $selected,
                    ];
                }
            }
            $this->lists['users'] = $formattedUsers;
        }

        return view('modules/product::payment.payment-v2');
    }

    public function setFilterCategory(int $id)
    {
        $this->filterCategoryId = $id;
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::select(['id', 'name'])->get();
    }

    #[Computed]
    public function quickProducts(): Collection
    {
        $branchId = user_branch();

        $query = Product::query()
            ->select(['id', 'name', 'price', 'qty', 'code', 'type'])
            ->with(['branches' => function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            }])
            ->limit(50); // Limit to prevent overload

        if ($this->filterCategoryId > 0) {
            $query->where('category_id', $this->filterCategoryId);
        }

        if (! empty($this->searchProduct)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchProduct . '%')
                  ->orWhere('code', 'like', '%' . $this->searchProduct . '%');
            });
        }

        // Get products and add branch_qty attribute
        return $query->orderBy('id', 'desc')->get()->map(function ($product) {
            // For services, qty is unlimited
            if ($product->type === 'service') {
                $product->branch_qty = -1; // -1 means unlimited
            } else {
                // Get qty from branch pivot, fallback to 0
                $product->branch_qty = $product->branches->first()?->pivot?->qty ?? 0;
            }

            return $product;
        });
    }
    public function selectedProduct($product_id): void
    {
        $product = Product::with(['branches' => function ($q) {
            $q->where('branches.id', user_branch());
        }])->find($product_id);

        if (! $product) {
            return;
        }

        // Allow services (unlimited stock)
        if ($product->type === 'service') {
            parent::selectedProduct($product_id);

            return;
        }

        // Check stock for current branch
        $qty = $product->branches->first()?->pivot->qty ?? 0;

        if ($qty <= 0) {
            $this->dispatch('browser-event', [
                'name' => 'toast',
                'title' => 'Lỗi',
                'message' => 'Sản phẩm đã hết hàng!',
                'type' => 'error',
            ]);

            return;
        }

        parent::selectedProduct($product_id);
    }
}
