/**
 * Displays a sitemap from a media
 *
 * @module controls/projects/project/media/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require Ajax
 */
define('controls/projects/project/media/Sitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Projects'

], function (QUIControl, QUISitemap, QUISitemapItem, QUIConfirm, Ajax, Projects) {
    "use strict";

    /**
     * A media sitemap
     *
     * @class controls/projects/project/media/Sitemap
     *
     * @fires onOpenBegin [Item, Control]
     * @fires onOpenEnd [Item, Control]
     * @fires onItemClick [Item, Control]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/media/Sitemap',

        Binds: [
            '$onInject',
            '$loadChildren',
            '$openSitemapItemSheetsWindow'
        ],

        options: {
            name     : 'projects-media-sitemap',
            container: false,
            project  : false,
            lang     : false,
            id       : false,
            limit    : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Elm = null;
            this.$Map = new QUISitemap();

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Returns the qui/controls/sitemap/Map Control
         *
         * @method controls/projects/project/media/Sitemap#getMap
         *
         * @return {Object} qui/controls/sitemap/Map
         */
        getMap: function () {
            return this.$Map;
        },

        /**
         * Create the DOMNode of the sitemap
         *
         * @method controls/projects/project/media/Sitemap#create
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = this.$Map.create();

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.open();
        },

        /**
         * Open the Map
         *
         * @method controls/projects/project/media/Sitemap#open
         */
        open: function () {
            if (!this.$Elm) {
                return;
            }

            var self    = this,
                id      = this.getAttribute('id') || 1,
                Project = Projects.get(
                    this.getAttribute('project'),
                    this.getAttribute('lang')
                );

            this.$Map.clearChildren();

            Project.getConfig(function (config) {
                // limits
                var projectLimit = 10;

                if ("adminSitemapMax" in config) {
                    projectLimit = parseInt(config.adminSitemapMax);
                }

                self.setAttribute('limit', projectLimit);

                self.getItem(id).then(function (result) {
                    self.$Map.clearChildren();

                    self.$addSitemapItem(
                        self.$Map,
                        self.$parseArrayToSitemapitem(result)
                    );

                    self.$Map.firstChild().open();
                });
            });
        },

        /**
         * Search the folder by value and select it
         * if the folder not exist
         * the sitemap search the parents and opens the path
         *
         * @method controls/projects/project/media/Sitemap#selectChildrenByValue
         * @param {Number} fileid
         */
        selectFolder: function (fileid) {
            var list = this.getChildrenByValue(fileid);

            if (list.length) {
                list.each(function (Itm) {
                    Itm.select();

                    if (!Itm.isOpen()) {
                        Itm.open();
                    }
                });
            }
        },

        /**
         * Search the a items by value and select the item
         *
         * @method controls/projects/project/media/Sitemap#selectChildrenByValue
         */
        selectChildrenByValue: function (value) {
            var items = this.$Map.getChildrenByValue(value);

            for (var i = 0, len = items.length; i < len; i++) {
                items[i].select();
            }
        },

        /**
         * Get specific children by value
         *
         * @method controls/projects/project/media/Sitemap#getChildrenByValue
         *
         * @param {String|Number} value
         * @return {Array}
         */
        getChildrenByValue: function (value) {
            return this.$Map.getChildrenByValue(value);
        },

        /**
         * Get all selected Items
         *
         * @method controls/projects/project/media/Sitemap#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren: function () {
            return this.$Map.getSelectedChildren();
        },

        /**
         * Get the attributes from a media item
         *
         * @method controls/projects/project/media/Sitemap#getItem
         *
         * @param {Number} id - Item ID
         * @param {Function} [callback] - call back function, if ajax is finish
         * @return {Promise}
         */
        getItem: function (id, callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_media_get', function (result) {
                    if (typeof callback === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    project: this.getAttribute('project'),
                    fileid : id,
                    onError: reject
                });
            }.bind(this));
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method controls/projects/project/media/Sitemap#$parseArrayToSitemapitem
         *
         * @param {Array} result
         * @return {Object} qui/controls/sitemap/Item
         *
         * @private
         * @ignore
         */
        $parseArrayToSitemapitem: function (result) {
            var Itm;

            var self = this,
                file = result.file || result;

            Itm = new QUISitemapItem({
                name       : file.name,
                index      : file.id,
                value      : file.id,
                text       : file.name,
                icon       : file.icon,
                type       : file.type,
                hasChildren: file.hasSubfolders || false,
                events     : {
                    onOpen : this.$loadChildren,
                    onClick: function (Itm) {
                        self.fireEvent('itemClick', [Itm, self]);
                    }
                }
            });

            if (file.active === false) {
                Itm.deactivate();
            }

            return Itm;
        },

        /**
         *
         * @param Item
         * @returns {Object} qui/controls/sitemap/Item
         */
        $loadChildren: function (Item) {
            var self = this;

            return new Promise(function (resolve, reject) {
                var limitStart   = Item.getAttribute('limitStart'),
                    projectLimit = self.getAttribute('limit');

                if (limitStart === false) {
                    limitStart = -1;
                }

                var start = (limitStart + 1) * projectLimit;

                Item.addIcon('fa fa-spinner fa-spin');
                Item.removeIcon(Item.getAttribute('icon'));

                self.fireEvent('openBegin', [Item, self]);

                // if children are false
                Ajax.get('ajax_media_getsubfolders', function (result) {
                    var count    = (result.count).toInt(),
                        end      = start + projectLimit,
                        sheets   = (count / projectLimit).ceil(),
                        children = result.children;

                    Item.setAttribute('hasChildren', count);
                    Item.clearChildren();

                    if (start > 0) {
                        Item.appendChild(
                            new QUISitemapItem({
                                icon       : 'fa fa-level-up',
                                text       : '...',
                                title      : 'Zurück - Rechtsklick für weitere Optionen',
                                contextmenu: false,
                                sheets     : sheets,
                                Item       : Item,
                                events     : {
                                    onClick : function () {
                                        Item.setAttribute('limitStart', limitStart - 1);
                                        self.$loadChildren(Item);
                                    },
                                    onInject: function (Me) {
                                        Me.getElm().addEvent('contextmenu', function (event) {
                                            event.stop();
                                            self.$openSitemapItemSheetsWindow(Me);
                                        });
                                    }
                                }
                            })
                        );
                    }

                    for (var i = 0, len = children.length; i < len; i++) {
                        self.$addSitemapItem(
                            Item,
                            self.$parseArrayToSitemapitem(children[i])
                        );
                    }

                    if (end < count) {
                        Item.appendChild(
                            new QUISitemapItem({
                                icon       : 'fa fa-level-down',
                                text       : '...',
                                title      : 'Vor - Rechtsklick für weitere Optionen', // #locale
                                contextmenu: false,
                                sheets     : sheets,
                                Item       : Item,
                                events     : {
                                    onClick : function () {
                                        Item.setAttribute('limitStart', limitStart + 1);
                                        self.$loadChildren(Item);
                                    },
                                    onInject: function (Me) {
                                        Me.getElm().addEvent('contextmenu', function (event) {
                                            event.stop();
                                            self.$openSitemapItemSheetsWindow(Me);
                                        });
                                    }
                                }
                            })
                        );
                    }

                    Item.removeIcon('fa-spinner');
                    Item.addIcon(Item.getAttribute('icon'));

                    self.fireEvent('openEnd', [Item, self]);

                    resolve();
                }, {
                    project: self.getAttribute('project'),
                    lang   : self.getAttribute('lang'),
                    fileid : Item.getAttribute('value'),
                    params : JSON.encode({
                        limit: start + ',' + projectLimit
                    }),
                    onError: reject
                });
            });

        },

        /**
         * Add the item to its parent
         * set the control attributes to the child item
         *
         * @method controls/projects/project/media/Sitemap#$addSitemapItem
         *
         * @param {Object} Parent - qui/controls/sitemap/Item
         * @param {Object} Child - qui/controls/sitemap/Item
         *
         * @private
         * @ignore
         */
        $addSitemapItem: function (Parent, Child) {
            if (Child.getAttribute('type') !== 'folder') {
                return;
            }

            Parent.appendChild(Child);
        },

        /**
         * Opens the sheet window for an item
         *
         * @ignore
         * @private
         * @param Item
         */
        $openSitemapItemSheetsWindow: function (Item) {
            if (!Item.getAttribute('sheets')) {
                return;
            }

            var self     = this,
                sheets   = (Item.getAttribute('sheets')).toInt(),
                Select   = new Element('select'),
                SiteItem = Item.getAttribute('Item');

            for (var i = 0, len = sheets; i < len; i++) {
                new Element('option', {
                    html : 'Blatt ' + (i + 1),
                    value: i
                }).inject(Select);
            }

            if (SiteItem.getAttribute('limitStart') !== false) {
                Select.value = (SiteItem.getAttribute('limitStart')).toInt() + 1;
            }


            new QUIConfirm({
                title    : 'Blätterfunktion',
                maxHeight: 300,
                maxWidth : 500,
                events   : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();

                        Content.set({
                            html   : '<p>Welche Unterordner des Ordnrs sollen angezeigt werden?</p>',
                            'class': 'qui-projects-sitemap-sheetsWindow'
                        });

                        Select.inject(Content);
                    },

                    onSubmit: function (Win) {
                        var Select = Win.getContent().getElement('select'),
                            sheet  = (Select.value).toInt();

                        SiteItem.setAttribute('limitStart', sheet - 1);
                        self.$loadChildren(SiteItem);
                    }
                }
            }).open();
        }
    });
});
