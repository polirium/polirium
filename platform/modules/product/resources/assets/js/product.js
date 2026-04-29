window.PoliriumProduct = {
    initCurrencyInput: function(el, wire) {
        if (typeof $ === 'undefined' || typeof Inputmask === 'undefined') return;

        $(el).inputmask({
            alias: 'numeric',
            groupSeparator: ',',
            autoGroup: true,
            digits: 0,
            digitsOptional: false,
            rightAlign: false,
            removeMaskOnSubmit: true
        })
        .on('change', function(e) {
            const val = Inputmask.unmask(el.value, { alias: 'numeric', digits: 0 });
            const numVal = val ? parseInt(val) : 0;
            if (wire) {
                // If the element has a wire:model, Livewire might handle it automatically,
                // but for complex masking, sometimes explicit set is safer.
                // However, we need to know the property name.
                // In the accounting example, we hardcoded 'input.value'.
                // Here we might need it to be dynamic or rely on x-model/wire:model.

                // If we pass the property name as an argument
                const modelName = el.getAttribute('wire:model') || el.getAttribute('wire:model.live');
                if (modelName) {
                    wire.set(modelName, numVal);
                }
            }
        });
    }
};
