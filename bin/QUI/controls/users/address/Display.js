/**
 * @module controls/users/address/Display
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 *
 * @event onLoad [self]
 * @event onLoadError [self]
 * @event onClick [self]
 */
define('controls/users/address/Display', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax'

], function (QUI, QUIControl, QUIAjax) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type: 'controls/users/address/Select',

        Binds: [
            'refresh',
            '$onInject'
        ],

        options: {
            addressId: false,
            userId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'user-address-display',
                events: {
                    click: function () {
                        this.fireEvent('click', [this]);
                    }.bind(this)
                }
            });

            return this.$Elm;
        },

        /**
         * refresh the display
         */
        refresh: function () {
            var self = this;

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_users_address_display', function (result) {
                    self.getElm().set('html', result);
                    resolve(result);
                }, {
                    uid: self.getAttribute('userId'),
                    aid: self.getAttribute('addressId')
                });
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

            this.refresh().then(function () {
                self.fireEvent('load', [self]);
            }).catch(function () {
                console.error(arguments);
                self.fireEvent('loadError', [self]);
            });
        }
    });
});
