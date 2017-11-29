/**
 * @module controls/packages/store/Store
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/packages/store/Store', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Ajax',

    'css!controls/packages/store/Store.css'

], function (QUI, QUIControl, QUILocale, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/store/Store',

        initialize: function (options) {
            this.parent(options);

            this.$Frame = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * event : on open
         */
        create: function () {
            this.$Elm = this.parent();
            this.$Elm.addClass('quiqqer-store');

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

            this.getStoreUrl().then(function (src) {
                self.$Frame = new Element('iframe', {
                    src   : src,
                    styles: {
                        border: 'none',
                        height: '100%',
                        width : '100%'
                    }
                }).inject(self.$Elm);

                self.fireEvent('load', [self]);
            });
        },

        /**
         * Get URL for package store
         *
         * @return {Promise}
         */
        getStoreUrl: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_packages_getStoreUrl', resolve, {
                    onError: reject
                });
            });
        },

        /**
         * Return the frame dom node
         *
         * @return {HTMLFrameElement}
         */
        getFrame: function () {
            return this.$Frame;
        }
    });
});
