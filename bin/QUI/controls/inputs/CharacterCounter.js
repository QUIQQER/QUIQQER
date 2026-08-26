/**
 * Character counter decorator for an input or textarea field.
 *
 * Attaches to an existing <input> or <textarea> via data-qui and shows a
 * numeric character counter below the field. It does not replace the field
 * (decorator pattern).
 *
 * Modes:
 *   - hard: native maxlength="M" is present   -> "count / M", warning at count === M
 *   - soft: data-qui-options-max="M" is set   -> "count / M", warning above M, error above M * 1.1
 *   - count only: neither is set              -> "count"
 *
 * Native maxlength wins over data-qui-options-max.
 *
 * @example
 * <textarea data-qui="controls/inputs/CharacterCounter"
 *           data-qui-options-max="1000"></textarea>
 */
define('controls/inputs/CharacterCounter', [

    'qui/QUI',
    'qui/controls/Control',

    'css!controls/inputs/CharacterCounter.css'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'controls/inputs/CharacterCounter',

        Binds: [
            'refresh',
            '$onImport'
        ],

        options: {
            max: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Field = null;
            this.$Counter = null;
            this.$limit = false;
            this.$hard = false;

            this.addEvent('import', this.$onImport);
        },

        /**
         * Attach the counter to the imported field.
         */
        $onImport: function () {
            this.$Field = this.getElm();

            if (!this.$Field) {
                return;
            }

            const hard = parseInt(this.$Field.getAttribute('maxlength'));
            const soft = parseInt(this.getAttribute('max'));

            if (hard > 0) {
                this.$limit = hard;
                this.$hard = true;
            } else if (soft > 0) {
                this.$limit = soft;
                this.$hard = false;
            } else {
                this.$limit = false;
                this.$hard = false;
            }

            this.$Counter = document.createElement('span');
            this.$Counter.className = 'qui-character-counter';

            // Insert in-flow, right after the field's container (the surrounding
            // <label> in the settings form), so it coexists with a description.
            const Container = this.$Field.closest('label') || this.$Field.parentNode;

            if (Container) {
                Container.insertAdjacentElement('afterend', this.$Counter);
            }

            this.$Field.addEventListener('input', this.refresh);

            this.refresh();
        },

        /**
         * Update the counter text and state. Public and bound, so it can also
         * be called after programmatic changes to the field value.
         */
        refresh: function () {
            if (!this.$Field || !this.$Counter) {
                return;
            }

            const count = this.$Field.value.length;

            this.$Counter.textContent = this.$limit
                ? count + ' / ' + this.$limit
                : '' + count;

            this.$Counter.classList.remove(
                'qui-character-counter--warning',
                'qui-character-counter--error'
            );

            if (this.$limit === false) {
                return;
            }

            if (this.$hard) {
                if (count >= this.$limit) {
                    this.$Counter.classList.add('qui-character-counter--warning');
                }

                return;
            }

            if (count > this.$limit * 1.1) {
                this.$Counter.classList.add('qui-character-counter--error');
            } else if (count > this.$limit) {
                this.$Counter.classList.add('qui-character-counter--warning');
            }
        }
    });
});
