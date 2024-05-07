/**
 * A group field / display
 * the display updates itself
 *
 * @module controls/projects/project/media/Entry
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/media/Entry', [

    'qui/controls/Control',
    'Locale',

    'css!controls/projects/project/media/Entry.css'

], function (QUIControl, Locale) {
    "use strict";

    /**
     * @class controls/projects/project/media/Entry
     *
     * @param {Number} gid - Item-ID
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/media/Entry',

        Binds: [
            '$onItemUpdate',
            'destroy'
        ],

        initialize: function (options) {
            this.parent(options);

            var Parent = this.getAttribute('Parent');

            this.$Media = Parent.getMedia();
            this.$Item  = null;
            this.$Elm   = null;
        },

        /**
         * Return the Item
         *
         * @return {Promise}
         */
        getItem: function () {
            if (this.$Item) {
                return Promise.resolve(this.$Item);
            }

            var self = this;

            return new Promise(function (resolve, reject) {
                self.$Media.get(self.getAttribute('id')).then(function (Item) {
                    self.$Item = Item;
                    self.$Item.addEvent('onRefresh', self.$onItemUpdate);

                    resolve(Item);
                }).catch(reject);
            });
        },

        /**
         * Create the DOMNode of the entry
         *
         * @method controls/projects/project/media/Entry#create
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'media-item-entry',

                html: '<span class="media-item-entry-icon fa fa-picture-o"></span>' +
                    '<span class="media-item-entry-text"></span>' +
                    '<span class="media-item-entry-close fa fa-remove"></span>'
            });

            var Close = this.$Elm.getElement('.media-item-entry-close');

            Close.addEvent('click', this.destroy);
            Close.set({
                alt  : Locale.get('quiqqer/core', 'items.entry.btn.remove'),
                title: Locale.get('quiqqer/core', 'items.entry.btn.remove')
            });

            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on entry destroy
         *
         * @method controls/projects/project/media/Entry#$onDestroy
         */
        $onDestroy: function () {
            this.$Item.removeEvent('refresh', this.$onItemUpdate);
        },

        /**
         * Refresh the data of the item
         *
         * @method controls/projects/project/media/Entry#refresh
         * @return {Object} this (controls/projects/project/media/Entry)
         */
        refresh: function () {
            var self      = this,
                EntryIcon = this.$Elm.getElement('.media-item-entry-icon');

            EntryIcon.removeClass('fa-picture-o');
            EntryIcon.addClass('fa-refresh');
            EntryIcon.addClass('fa-spin');

            this.getItem().then(function () {
                if (self.$Item.isLoaded()) {
                    self.$onItemUpdate(self.$Item);
                    return;
                }

                self.$Item.refresh();
            });

            return this;
        },

        /**
         * Update the item name
         *
         * @method controls/projects/project/media/Entry#$onItemUpdate
         * @param {Object} Item - classes/groups/Group
         * @return {Object} this (controls/projects/project/media/Entry)
         */
        $onItemUpdate: function (Item) {
            if (!this.$Elm) {
                return this;
            }

            var ItemIcon = this.$Elm.getElement('.media-item-entry-icon');

            ItemIcon.addClass('fa-picture-o');
            ItemIcon.removeClass('fa-refresh');
            ItemIcon.removeClass('fa-spin');

            this.$Elm
                .getElement('.media-item-entry-text')
                .set('html', Item.getAttribute('name'));

            return this;
        }
    });
});
