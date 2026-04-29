<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/accounting::accounting.invoice_list') }}
    </x-slot:title>

    <x-slot:actions>
        <button class="btn btn-primary d-none d-sm-inline-block" onclick="window.Livewire.dispatch('show-modal-create-sale-invoice')">
            {!! tabler_icon('plus') !!}
            {{ trans('modules/accounting::accounting.add_new') }}
        </button>
    </x-slot:actions>

    <div class="row" x-data="{ showSidebar: true }" @toggle-sidebar.window="showSidebar = !showSidebar">
        <div class="col-md-3" x-show="showSidebar" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-full">
            @livewire('modules/accounting::payment.filter-sidebar')
        </div>
        <div :class="showSidebar ? 'col-md-9' : 'col-md-12'" class="transition-all duration-300">
            <div class="mb-3" x-show="!showSidebar" x-cloak>
                <button type="button" class="btn btn-outline-primary" @click="showSidebar = true">
                    {!! tabler_icon('filter', ['class' => 'icon']) !!} {{ trans('modules/accounting::accounting.show_filter') }}
                </button>
            </div>
            <x-ui::card>
                @livewire('modules/accounting::payment.datatable.payment-table')
            </x-ui::card>
        </div>
    </div>

    {{-- Quick Create Sale Invoice Modal --}}
    @livewire('modules/accounting::payment.modal.modal-create-sale-invoice')
    @livewire('modules/accounting::payment.modal.modal-quick-update-component')

    {{-- Quick Create Modals --}}
    @livewire('modules/customer::index.modal.modal-create-customer')
    @livewire('modules/product::payment.modal.modal-create-sale-channel')
    @livewire('modules/product::payment.modal.modal-create-partner-delivery')
    @livewire('modules/product::payment.modal.modal-create-payment-method')

    @push('scripts')
    <script>
        (function initDragScroll() {
            if (window._dragScrollInitialized) return;
            window._dragScrollInitialized = true;

            let isDown = false;
            let isDragging = false;
            let startX;
            let scrollLeft;
            let activeSlider = null;
            const DRAG_THRESHOLD = 5; // Pixels before drag starts, allows text selection

            document.addEventListener('mousedown', (e) => {
                const slider = e.target.closest('.table-responsive');
                if (!slider) return;

                // Prevent dragging if clicking interactable elements or detail view
                if (['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;
                if (e.target.closest('a') || e.target.closest('button') || e.target.closest('.invoice-detail-selectable')) return;

                isDown = true;
                isDragging = false;
                activeSlider = slider;
                startX = e.pageX - activeSlider.offsetLeft;
                scrollLeft = activeSlider.scrollLeft;
            });

            const resetDrag = () => {
                isDown = false;
                isDragging = false;
                if (activeSlider) {
                    activeSlider.style.cursor = 'auto';
                    activeSlider.style.removeProperty('user-select');
                    activeSlider = null;
                }
            };

            document.addEventListener('mouseup', resetDrag);
            window.addEventListener('mouseup', resetDrag);

            document.addEventListener('mousemove', (e) => {
                if (!isDown || !activeSlider) return;
                const x = e.pageX - activeSlider.offsetLeft;
                const distance = Math.abs(x - startX);

                // Only start dragging after exceeding threshold
                if (!isDragging && distance < DRAG_THRESHOLD) return;

                if (!isDragging) {
                    isDragging = true;
                    activeSlider.style.cursor = 'grabbing';
                    activeSlider.style.userSelect = 'none';
                }

                e.preventDefault();
                const walk = (x - startX) * 1.5;
                activeSlider.scrollLeft = scrollLeft - walk;
            });
        })();
    </script>
    @endpush
</x-ui.layouts::app>
