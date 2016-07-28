/**
 * @module package/quiqqer/products/bin/controls/products/SelectItem
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/classes/Products
 * @require css!package/quiqqer/products/bin/controls/products/SelectItem.css
 */
define('controls/usersAndGroups/SelectItem', [

    'qui/controls/Control',
    'Ajax',

    'css!controls/usersAndGroups/SelectItem.css'

], function (QUIControl, Handler) {
    "use strict";

    var Products = new Handler();

    return new Class({
        Extends: QUIControl,
        Type   : 'controls/usersAndGroups/SelectItem',

        Binds: [
            '$onInject'
        ],

        options: {
            id: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Icon    = null;
            this.$Text    = null;
            this.$Destroy = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLElement}
         */
        create: function () {
            var self = this,
                Elm  = this.parent();

            Elm.set({
                'class': 'quiqqer-usersAndGroups-selectItem smooth',
                html   : '<span class="quiqqer-usersAndGroups-selectItem-icon fa fa-groups"></span>' +
                         '<span class="quiqqer-usersAndGroups-selectItem-text">&nbsp;</span>' +
                         '<span class="quiqqer-usersAndGroups-selectItem-destroy fa fa-remove"></span>'
            });

            this.$Icon    = Elm.getElement('.quiqqer-usersAndGroups-selectItem-icon');
            this.$Text    = Elm.getElement('.quiqqer-usersAndGroups-selectItem-text');
            this.$Destroy = Elm.getElement('.quiqqer-usersAndGroups-selectItem-destroy');

            this.$Destroy.addEvent('click', function () {
                self.destroy();
            });

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.$Text.set({
                html: '<span class="fa fa-spinner fa-spin"></span>'
            });

            Products.getChild(
                this.getAttribute('id')
            ).then(function (data) {
                self.$Text.set('html', data.title);
            }).catch(function () {
                self.$Icon.removeClass('fa-groups');
                self.$Icon.addClass('fa-bolt');
                self.$Text.set('html', '...');
            });
        }
    });
});
