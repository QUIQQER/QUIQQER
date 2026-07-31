/**
 * Window for editing a VHost.
 */
define('controls/system/vhost/EditWindow', [

    'qui/controls/windows/Confirm',
    'controls/system/VHost',
    'controls/system/VHostServerCode',
    'Locale'

], function (QUIConfirm, VHost, VHostServerCode, QUILocale) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIConfirm,
        Type: 'controls/system/vhost/EditWindow',

        Binds: [
            '$onOpen'
        ],

        options: {
            vhost: false,
            icon: 'fa fa-location-arrow',
            maxHeight: 700,
            maxWidth: 850,
            autoclose: false,
            ok_button: {
                text: QUILocale.get(lg, 'system.vhosts.edit.window.btn.save'),
                textimage: 'fa fa-save'
            },
            cancel_button: {
                text: QUILocale.get(lg, 'system.vhosts.edit.window.btn.cancel'),
                textimage: 'fa fa-remove'
            }
        },

        initialize: function (options) {
            options = options || {};
            options.title = QUILocale.get(lg, 'system.vhosts.edit.window.title', {
                vhost: options.vhost || ''
            });

            this.parent(options);

            this.$VHostControl = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * Render the matching VHost editor.
         */
        $onOpen: function () {
            const vhost = this.getAttribute('vhost');
            const Content = this.getContent();

            if (!vhost) {
                this.close();
                return;
            }

            Content.innerHTML = '';
            this.Loader.show();

            const Control = /^\d+$/.test(vhost) ? VHostServerCode : VHost;

            this.$VHostControl = new Control({
                host: vhost
            }).inject(Content);

            this.Loader.hide();
        },

        /**
         * Save the embedded VHost control.
         *
         * @return {Promise}
         */
        submit: function () {
            if (!this.$VHostControl) {
                return Promise.reject();
            }

            this.Loader.show();

            return new Promise((resolve, reject) => {
                const submitted = this.$VHostControl.save(() => {
                    this.Loader.hide();
                    this.fireEvent('submit', [this]);
                    this.close();
                    resolve();
                });

                if (submitted === false) {
                    this.Loader.hide();
                    reject();
                }
            });
        }
    });
});
