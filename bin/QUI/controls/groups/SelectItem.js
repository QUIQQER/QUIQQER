/**
 * Group select item
 *
 * @module controls/groups/SelectItem
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require Groups
 * @require css!controls/groups/SelectItem.css
 */
define('controls/groups/SelectItem', [

    'qui/controls/Control',
    'Groups',

    'css!controls/groups/SelectItem.css'

], function (QUIControl, Groups) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'controls/groups/SelectItem',

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
                'class': 'quiqer-group-selectItem smooth',
                html   : '<span class="quiqer-group-selectItem-icon fa fa-group"></span>' +
                         '<span class="quiqer-group-selectItem-text">&nbsp;</span>' +
                         '<span class="quiqer-group-selectItem-destroy fa fa-remove"></span>'
            });

            this.$Icon    = Elm.getElement('.quiqer-group-selectItem-icon');
            this.$Text    = Elm.getElement('.quiqer-group-selectItem-text');
            this.$Destroy = Elm.getElement('.quiqer-group-selectItem-destroy');

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

            Groups.get(this.getAttribute('id')).load().then(function (Group) {
                self.$Text.set('html', Group.getAttribute('name'));
            }).catch(function () {
                self.$Icon.removeClass('fa-group');
                self.$Icon.addClass('fa-bolt');
                self.$Text.set('html', '...');
            });
        }
    });
});
