window.PoliriumAccounting = {
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
                wire.set('input.value', numVal);
            }
        });
    }
};
