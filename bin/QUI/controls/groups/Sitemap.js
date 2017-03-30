/**
 * @module controls/groups/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * A Sitemap that list the groups
 *
 * @events onItemClick [ this, {qui/controls/sitemap/Item} ]
 * @events onItemDblClick [ this, {qui/controls/sitemap/Item} ]
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require Locale
 * @require Ajax
 * @require Groups
 */
define('controls/groups/Sitemap', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Locale',
    'Ajax',
    'Groups'

], function (QUI, QUIControl, QUISitemap, QUISitemapItem, QUILocale, Ajax, Groups) {
    "use strict";

    /**
     * @class controls/groups/Sitemap
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/groups/Sitemap',

        Binds: [
            'getChildren',
            '$onItemClick',
            '$onDrawEnd'
        ],

        options: {
            multible: false
        },

        $Map      : null,
        $Container: null,

        initialize: function (options) {
            this.parent(options);

            this.$Map = null;

            this.addEvent('onDrawEnd', this.$onDrawEnd);
        },

        /**
         * Create the DomNode Element of the Control
         *
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div.qui-group-sitemap');

            this.$Map = new QUISitemap({
                name    : 'Group-Sitemap',
                multible: this.getAttribute('multible')
            });

            // Firstchild
            this.$Map.appendChild(
                new QUISitemapItem({
                    name       : 1,
                    index      : 1,
                    value      : 1,
                    text       : '',
                    alt        : '',
                    hasChildren: false,
                    events     : {
                        onOpen    : this.getChildren,
                        onClick   : this.$onItemClick,
                        onDblClick: this.$onItemDblClick
                    }
                })
            );

            this.$Map.inject(this.$Elm);

            (function () {
                self.$onDrawEnd();
            }).delay(200);

            return this.$Elm;
        },

        /**
         * the DOMNode is injected, then call the root group
         */
        $onDrawEnd: function () {
            var self  = this,
                Map   = this.$Map,
                First = this.$Map.firstChild();

            // load first child
            Ajax.get('ajax_groups_root', function (result) {
                if (!result) {

                    QUI.getMessageHandler().then(function (MH) {
                        MH.addAttention(
                            QUILocale.get('quiqqer/system', 'message.unknown.root.group')
                        );
                    });

                    return;
                }


                First.setAttributes({
                    name       : result.name,
                    index      : result.id,
                    value      : result.id,
                    text       : result.name,
                    alt        : result.name,
                    icon       : 'fa fa-group',
                    hasChildren: result.hasChildren
                });

                First.open();
                First.select();

                Promise.all([
                    Groups.get(1).load(), // Everyone
                    Groups.get(0).load()  // Guest
                ]).then(function (data) {
                    var everyone = data[0],
                        guest    = data[1];

                    // guest
                    Map.appendChild(
                        new QUISitemapItem({
                            index      : guest.attributes.id,
                            value      : guest.attributes.id,
                            name       : guest.attributes.name,
                            text       : guest.attributes.name,
                            alt        : guest.attributes.name,
                            icon       : 'fa fa-group',
                            hasChildren: 0,
                            events     : {
                                onClick   : self.$onItemClick,
                                onDblClick: self.$onItemDblClick
                            }
                        })
                    );

                    // everyone
                    Map.appendChild(
                        new QUISitemapItem({
                            index      : everyone.attributes.id,
                            value      : everyone.attributes.id,
                            name       : everyone.attributes.name,
                            text       : everyone.attributes.name,
                            alt        : everyone.attributes.name,
                            icon       : 'fa fa-group',
                            hasChildren: 0,
                            events     : {
                                onClick   : self.$onItemClick,
                                onDblClick: self.$onItemDblClick
                            }
                        })
                    );
                });
            });
        },

        /**
         * Display the children of the sitemap item
         *
         * @param {Object} Parent - qui/controls/sitemap/Item
         */
        getChildren: function (Parent) {
            Parent.removeIcon('fa-group');
            Parent.addIcon('fa fa-spinner fa-spin');

            var self  = this,
                Group = Groups.get(Parent.getAttribute('value'));

            Group.getChildren().then(function (result) {
                var i, len, entry;

                Parent.clearChildren();

                for (i = 0, len = result.length; i < len; i++) {
                    entry = result[i];

                    Parent.appendChild(
                        new QUISitemapItem({
                            name       : entry.name,
                            index      : entry.id,
                            value      : entry.id,
                            text       : entry.name,
                            alt        : entry.name,
                            icon       : 'fa fa-group',
                            hasChildren: entry.hasChildren,
                            events     : {
                                onOpen    : self.getChildren,
                                onClick   : self.$onItemClick,
                                onDblClick: self.$onItemDblClick
                            }
                        })
                    );
                }

                Parent.removeIcon('fa-spinner');
                Parent.addIcon('fa fa-group');
            });
        },

        /**
         * Return the values of the selected sitemap items
         *
         * @return {Array}
         */
        getValues: function () {
            var i, len;

            var sels   = this.$Map.getSelectedChildren(),
                result = [];

            for (i = 0, len = sels.length; i < len; i++) {
                result.push(
                    sels[i].getAttribute('value')
                );
            }

            return result;
        },

        /**
         * event : click on a sitemap item
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $onItemClick: function (Item) {
            this.fireEvent('onItemClick', [this, Item]);
        },

        /**
         * event : click on a sitemap item
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $onItemDblClick: function (Item) {
            this.fireEvent('onItemDblClick', [this, Item]);
        }
    });
});
