<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ trans('modules/product::product.stock') }}
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            @livewire('modules/product::stock.filter-sidebar')
        </div>
        <div class="col-md-9">
            <x-ui::card>
                @livewire('modules/product::stock-table')
            </x-ui::card>
        </div>
    </div>

    @push('scripts')
    <script>
        (function initDragScroll() {
            if (window._dragScrollInitialized) return;
            window._dragScrollInitialized = true;

            let isDown = false;
            let startX;
            let scrollLeft;
            let activeSlider = null;

            document.addEventListener('mousedown', (e) => {
                const slider = e.target.closest('.table-responsive');
                if (!slider) return;

                // Prevent dragging if clicking interactable elements or detail view
                if (['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;
                if (e.target.closest('a') || e.target.closest('button') || e.target.closest('.invoice-detail-selectable')) return;

                isDown = true;
                activeSlider = slider;
                activeSlider.style.cursor = 'grabbing';
                activeSlider.style.userSelect = 'none'; // Prevent text selection
                startX = e.pageX - activeSlider.offsetLeft;
                scrollLeft = activeSlider.scrollLeft;
            });

            document.addEventListener('mouseup', () => {
                isDown = false;
                if (activeSlider) {
                    activeSlider.style.cursor = 'auto';
                    activeSlider.style.removeProperty('user-select');
                    activeSlider = null;
                }
            });

            // Use window mouseup to catch releases outside the element
            window.addEventListener('mouseup', () => {
                if (isDown) {
                    isDown = false;
                    if (activeSlider) {
                        activeSlider.style.cursor = 'auto';
                        activeSlider.style.removeProperty('user-select');
                        activeSlider = null;
                    }
                }
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDown || !activeSlider) return;
                e.preventDefault();
                const x = e.pageX - activeSlider.offsetLeft;
                const walk = (x - startX) * 1.5; // Scroll speed
                activeSlider.scrollLeft = scrollLeft - walk;
            });
        })();
    </script>
    @endpush
</x-ui.layouts::app>
