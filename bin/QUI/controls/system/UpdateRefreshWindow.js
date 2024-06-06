define('controls/system/UpdateRefreshWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale'

], function (QUI, QUIConfirm, QUILocale) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/system/UpdateRefreshWindow',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                icon       : 'fa fa-exclamation',
                texticon   : 'fa fa-exclamation',
                maxHeight  : 400,
                maxWidth   : 600,
                information: QUILocale.get(lg, 'update.refresh.information'),
                title      : QUILocale.get(lg, 'update.refresh.title'),
                text       : QUILocale.get(lg, 'update.refresh.text'),

                ok_button: {
                    text     : QUILocale.get(lg, 'update.refresh.submit'),
                    textimage: 'fa fa-refresh'
                }
            });

            this.addEvents({
                onSubmit   : this.$onSubmit,
                onOpen     : this.$onOpen,
                onOpenBegin: this.$onOpenBegin
            });
        },

        $onOpenBegin: function () {
            if (document.getElement('.window-update-refresh-info')) {
                this.close();
            }
        },

        $onOpen: function () {
            if (document.getElement('.window-update-refresh-info')) {
                this.close();
                return;
            }

            this.getElm().addClass('window-update-refresh-info');
        },

        $onSubmit: function () {
            window.location.reload();
        }
    });
});