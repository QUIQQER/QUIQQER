/**
 * Help panel
 *
 * @module controls/desktop/panels/Help
 * @author www.pcsg.de (Henning Leutz)
 *
 * @deprecated
 */
define('controls/desktop/panels/Help', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'Locale'

], function (QUI, QUIPanel, QUIButton, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/desktop/panels/Help',

        Binds: [
            '$onCreate',
            '$onResize'
        ],

        initialize: function (options) {
            var self = this;

            this.parent(options);

            this.setAttribute('title', QUILocale.get('quiqqer/quiqqer', 'help.panel.title'));
            this.setAttribute('icon', 'fa fa-h-square');

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: function () {
                    (function () {
                        self.destroy();
                    }).delay(500);
                }
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            // nothing
        }
    });
});
