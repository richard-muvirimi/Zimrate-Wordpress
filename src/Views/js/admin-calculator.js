/**
 * Zimrate calculator scripts
 *
 * Rates arrive quoted against USD, so every other base is a cross rate worked
 * out here. That keeps changing the base instant and needs no request.
 *
 * @since 1.1.6
 */
(function () {
    'use strict';

    /**
     * Round to the configured precision without trailing zeroes
     *
     * @param {number} value
     * @param {number} precision
     * @returns {string}
     */
    function format(value, precision) {
        if (!isFinite(value)) {
            return '';
        }

        return String(parseFloat(value.toFixed(precision)));
    }

    /**
     * Wire up a single calculator instance
     *
     * @param {HTMLElement} root
     */
    function setup(root) {
        var usd;

        try {
            usd = JSON.parse(root.getAttribute('data-rates') || '{}');
        } catch (e) {
            return;
        }

        if (!Object.keys(usd).length) {
            return;
        }

        var precision = parseInt(root.getAttribute('data-precision'), 10);
        var cushion = parseFloat(root.getAttribute('data-cushion'));

        if (isNaN(precision)) {
            precision = 2;
        }

        if (isNaN(cushion)) {
            cushion = 0;
        }

        var amount = root.querySelector('[data-zimrate-amount]');
        var result = root.querySelector('[data-zimrate-result]');
        var baseSelect = root.querySelector('[data-zimrate-base]');
        var select = root.querySelector('[data-zimrate-currency]');
        var swap = root.querySelector('[data-zimrate-swap]');
        var summary = root.querySelector('[data-zimrate-summary]');
        var heading = root.querySelector('[data-zimrate-heading]');

        if (!amount || !result || !select || !baseSelect) {
            return;
        }

        // false converts base -> currency, true converts the other way
        var reversed = false;

        /**
         * Cross rate for a currency against the chosen base, cushion applied
         *
         * @param {string} to
         * @returns {number}
         */
        function rateFor(to) {
            var base = baseSelect.value;
            var rate;

            if (to === base) {
                rate = 1;
            } else if (base === 'USD') {
                rate = usd[to];
            } else {
                rate = usd[to] / usd[base];
            }

            return rate + (cushion * rate) / 100;
        }

        /**
         * Recalculate from whichever field the reader is typing in
         */
        function convert() {
            var base = baseSelect.value;
            var currency = select.value;
            var rate = rateFor(currency);
            var source = reversed ? result : amount;
            var target = reversed ? amount : result;
            var value = parseFloat(source.value);

            if (isNaN(value)) {
                target.value = '';
            } else {
                target.value = format(reversed ? value / rate : value * rate, precision);
            }

            if (summary) {
                summary.textContent = '1 ' + base + ' = ' +
                    format(rate, precision) + ' ' + currency;
            }

            root.classList.toggle('is-reversed', reversed);
        }

        /**
         * Redraw the rates table against the chosen base
         */
        function redraw() {
            var base = baseSelect.value;

            if (heading) {
                heading.textContent = heading.textContent.replace(
                    /\(per 1 .*\)/,
                    '(per 1 ' + base + ')'
                );
            }

            var rows = root.querySelectorAll('[data-zimrate-row]');

            Array.prototype.forEach.call(rows, function (row) {
                var cell = row.querySelector('[data-zimrate-cell]');

                if (cell) {
                    cell.textContent = format(
                        rateFor(row.getAttribute('data-zimrate-row')),
                        precision
                    );
                }
            });
        }

        amount.addEventListener('input', function () {
            reversed = false;
            convert();
        });

        result.addEventListener('input', function () {
            reversed = true;
            convert();
        });

        select.addEventListener('change', convert);

        baseSelect.addEventListener('change', function () {
            redraw();
            convert();
        });

        if (swap) {
            swap.addEventListener('click', function () {
                reversed = !reversed;

                // the field being read from becomes the editable one
                amount.readOnly = reversed;
                result.readOnly = !reversed;

                convert();
            });
        }

        // picking a row in the table converts against that currency
        var rows = root.querySelectorAll('[data-zimrate-row]');

        Array.prototype.forEach.call(rows, function (row) {
            row.addEventListener('click', function () {
                select.value = row.getAttribute('data-zimrate-row');
                convert();
            });
        });

        convert();
    }

    /**
     * Boot every calculator on the page
     */
    function init() {
        var roots = document.querySelectorAll('[data-zimrate-calculator]');

        Array.prototype.forEach.call(roots, setup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
