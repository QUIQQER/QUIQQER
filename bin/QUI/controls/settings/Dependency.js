/**
 * Shows / hides dependent form fields based on the value of this field.
 *
 * Attach via data-qui to the controlling field (select / input). Dependent
 * fields in the same form (or settings table) declare:
 *
 *   data-dependency="<name of the controlling field>"
 *   data-dependency-options="valueA,valueB"   -> visible only for these values
 *   data-dependency-options="!valueA,!valueB" -> hidden for these values
 *
 * Use either positive or negated entries per field, do not mix both.
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
            const value = this.$Input.value || '';

            this.$Fields.forEach(function (Field) {
                const entries = (Field.getAttribute('data-dependency-options') || '')
                    .split(',')
                    .map(function (entry) {
                        return entry.trim();
                    })
                    .filter(function (entry) {
                        return entry !== '';
                    });

                const positives = entries.filter(function (entry) {
                    return entry.charAt(0) !== '!';
                });

                const negatives = entries.map(function (entry) {
                    return entry.charAt(0) === '!' ? entry.substring(1) : null;
                }).filter(function (entry) {
                    return entry !== null;
                });

                let visible = !positives.length || positives.indexOf(value) !== -1;

                if (negatives.indexOf(value) !== -1) {
                    visible = false;
                }

                const Row = Field.closest('[data-dependency-row]')
                    || Field.closest('tr')
                    || Field;

                Row.style.display = visible ? null : 'none';
            });
        }
    });
});
