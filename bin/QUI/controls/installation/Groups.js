/**
 * @module controls/installation/Groups
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/installation/Groups', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/installation/Groups',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const AddGroups = this.getElm().getElement('[name="add-quiqqer-groups"]');
            const NoGroups = this.getElm().getElement('[name="no-quiqqer-groups"]');

            AddGroups.addEvent('click', () => {
                NoGroups.checked = false;
            });

            NoGroups.addEvent('click', function () {
                AddGroups.checked = false;
            });
        },

        next: function () {
            const AddGroups = this.getElm().getElement('[name="add-quiqqer-groups"]');
            const NoGroups = this.getElm().getElement('[name="no-quiqqer-groups"]');

            if (!AddGroups.checked && !NoGroups.checked) {
                NoGroups.required = 'required';
                this.triggerError(NoGroups);

                setTimeout(function () {
                    NoGroups.required = false;
                }, 1000);
            }

            return AddGroups.checked || NoGroups.checked;
        },

        triggerError: function (Node) {
            if ("checkValidity" in Node) {
                Node.checkValidity();
            }

            // chrome validate message
            if ("reportValidity" in Node) {
                Node.reportValidity();
            }
        }
    });
});
