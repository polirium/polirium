{{-- Selling price edited in place: saved when the field changes (Enter or leaving it). --}}
<div
    class="pg-price-input"
    x-data="{
        value: @js(core_number_format($product->price)),
        saved: @js(core_number_format($product->price)),
        state: '',
        save() {
            if (this.value === this.saved) return;
            this.state = 'saving';
            $wire.updatePrice({{ $product->id }}, this.value)
                .then((formatted) => {
                    if (formatted === null) { this.value = this.saved; this.state = 'error'; return; }
                    this.value = this.saved = formatted;
                    this.state = 'saved';
                    setTimeout(() => { if (this.state === 'saved') this.state = ''; }, 1500);
                })
                .catch(() => { this.value = this.saved; this.state = 'error'; });
        },
    }"
>
    <input
        type="text"
        inputmode="numeric"
        class="form-control form-control-sm text-end"
        :class="{ 'is-valid': state === 'saved', 'is-invalid': state === 'error' }"
        x-model="value"
        x-on:change="save()"
        x-on:keydown.enter.prevent="$el.blur()"
        x-on:keydown.escape="value = saved; $el.blur()"
        aria-label="{{ __('modules/product::product.selling_price_column') }} {{ $product->code }}"
    >
</div>
