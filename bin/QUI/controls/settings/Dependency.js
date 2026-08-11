/**
 * Shows / hides dependent form fields based on the value of this field.
 *
 * Attach via data-qui to the controlling field (select / input / checkbox).
 * Dependent fields in the same form (or settings table) declare:
 *
 *   data-dependency="<name of the controlling field>"
 *   data-dependency-options="valueA,valueB"   -> visible only for these values
 *   data-dependency-options="!valueA,!valueB" -> hidden for these values
 *   data-dependency-options="*"                -> visible while the field is filled (non-empty)
 *   data-dependency-options="!*"               -> visible while the field is empty
 *
 * Use either positive or negated entries per field, do not mix both. The
 * filled markers "*" / "!*" are evaluated on their own and take precedence
 * over value lists, so they are not combined with concrete values.
 *
 * A checkbox is treated as the value "1" when checked and "0" otherwise, so
 * the same options apply. For "*" / "!*" a checkbox counts as filled while it
 * is checked:
 *
 *   (no data-dependency-options) -> visible while the checkbox is checked
 *   data-dependency-options="1"  -> visible while the checkbox is checked
 *   data-dependency-options="0"  -> visible while the checkbox is unchecked
 *   data-dependency-options="!1" -> visible while the checkbox is unchecked
 *
 * The element that gets hidden is, in order of precedence:
 *   - the closest wrapper with a data-dependency-row attribute
 *   - the closest <tr> (XML settings table layout)
 *   - the field itself
 *
 * @module package/quiqqer/core/bin/QUI/controls/settings/Dependency
 */
define('package/quiqqer/core/bin/QUI/controls/settings/Dependency', [
    'qui/controls/Control'
], function (QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type: 'package/quiqqer/core/bin/QUI/controls/settings/Dependency',

        Binds: [
            '$onImport',
            '$applyState'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Fields = [];

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            this.$Input = this.getElm();

            if (!this.$Input) {
                return;
            }

            const Scope = this.$Input.closest('form') || this.$Input.closest('table');

            if (!Scope) {
                return;
            }

            this.$Fields = Array.from(
                Scope.querySelectorAll('[data-dependency="' + this.$Input.name + '"]')
            );

            this.$Input.addEventListener('change', this.$applyState);
            this.$applyState();
        },

        $applyState: function () {
            const isCheckbox = this.$Input.type === 'checkbox';
            const value = isCheckbox
                ? (this.$Input.checked ? '1' : '0')
                : (this.$Input.value || '');
            const isFilled = isCheckbox
                ? this.$Input.checked
                : String(value).trim() !== '';

            this.$Fields.forEach(function (Field) {
                const entries = (Field.getAttribute('data-dependency-options') || '')
                    .split(',')
                    .map(function (entry) {
                        return entry.trim();
                    })
                    .filter(function (entry) {
                        return entry !== '';
                    });

                let visible;

                if (entries.indexOf('*') !== -1) {
                    visible = isFilled;
                } else if (entries.indexOf('!*') !== -1) {
                    visible = !isFilled;
                } else if (!entries.length) {
                    visible = isCheckbox ? this.$Input.checked : true;
                } else {
                    const positives = entries.filter(function (entry) {
                        return entry.charAt(0) !== '!';
                    });

                    const negatives = entries.map(function (entry) {
                        return entry.charAt(0) === '!' ? entry.substring(1) : null;
                    }).filter(function (entry) {
                        return entry !== null;
                    });

                    visible = !positives.length || positives.indexOf(value) !== -1;

                    if (negatives.indexOf(value) !== -1) {
                        visible = false;
                    }
                }

                const Row = Field.closest('[data-dependency-row]')
                    || Field.closest('tr')
                    || Field;

                Row.style.display = visible ? null : 'none';
            }.bind(this));
        }
    });
});
