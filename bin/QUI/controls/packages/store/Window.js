/**
 * @module controls/packages/store/Window
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/packages/store/Window', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'controls/packages/store/Store'

], function (QUI, QUIPopup, Store) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/packages/store/Window',

        Binds: [
            '$onOpen'
        ],

        options: {
            package  : false,
            maxHeight: 600,
            maxWidth : 800
        },

        initialize: function (options) {
            var size = QUI.getWindowSize();

            this.setAttributes({
                maxHeight: size.y - 100,
                maxWidth : size.x - 100,
                title    : 'Q-Store',
                buttons  : false
            });

            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            var self = this;

            this.Loader.show();

            new Store({
                package: this.getAttribute('package'),
                events : {
                    onLoad: function () {
                        self.Loader.hide();
                    }
                }
            }).inject(this.getContent());
        }
    });
});
