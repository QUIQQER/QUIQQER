/**
 * @module controls/installation/Workspace
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/installation/Workspace', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    let WORKSPACE = '2-columns';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/installation/Workspace',

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
            const buttons = this.getElm().getElements('button');
            const Field = this.getElm().getElement('[name="workspace-columns"]');

            buttons.addEvent('click', function(e) {
                e.stop();

                let Button = e.target;

                if (Button.nodeName !== 'BUTTON') {
                    Button = Button.getParent('button');
                }

                buttons.removeClass('quiqqer-installation-workspace-list-button--active');
                Button.addClass('quiqqer-installation-workspace-list-button--active');

                Field.value = Button.name;
            });

            this.getElm().getElement('[name="2-columns"]').addClass(
                'quiqqer-installation-workspace-list-button--active'
            );
        }
    });
});
