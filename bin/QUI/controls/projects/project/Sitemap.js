/**
 * Displays a Sitemap from a project
 *
 * @module controls/projects/project/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require qui/controls/contextmenu/Item
 * @require qui/controls/contextmenu/Seperator
 * @require Projects
 * @require Ajax
 * @require Locale
 * @require Clipboard
 * @require utils/Site
 * @require css!controls/projects/project/Sitemap.css
 */

define('controls/projects/project/Sitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Seperator',
    'qui/controls/windows/Confirm',

    'Projects',
    'Ajax',
    'Locale',
    'Clipboard',
    'utils/Site',

    'css!controls/projects/project/Sitemap.css'

], function () {
    "use strict";

    var QUIControl              = arguments[0],
        QUISitemap              = arguments[1],
        QUISitemapItem          = arguments[2],
        QUIContextmenuItem      = arguments[3],
        QUIContextmenuSeperator = arguments[4],
        QUIConfirm              = arguments[5],

        Projects                = arguments[6],
        Ajax                    = arguments[7],
        Locale                  = arguments[8],
        Clipboard               = arguments[9],
        SiteUtils               = arguments[10];

    /**
     * A project sitemap
     *
     * @class controls/projects/project/Sitemap
     *
     * @fires onOpenBegin [Item, this]
     * @fires onOpenEnd [Item, this]
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/Sitemap',

        Binds: [
            'onSiteChange',
            'onSiteCreate',
            'onSiteDelete',

            '$open',
            '$close'
        ],

        options: {
            name     : 'projects-site-panel',
            container: false,
            project  : false,
            lang     : false,
            id       : false,
            media    : false, // show the media in the sitemap, too
            multible : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Elm = null;
            this.$Map = new QUISitemap({
                multible: this.getAttribute('multible')
            });

            this.$Map.setParent(this);

            this.$Project = Projects.get(
                this.getAttribute('project'),
                this.getAttribute('lang')
            );

            var self = this;

            this.addEvent('onDestroy', function () {
                if (self.$Map) {
                    self.$Map.destroy();
                }

                self.$Project.removeEvents({
                    onSiteCreate    : self.onSiteCreate,
                    onSiteSave      : self.onSiteChange,
                    onSiteActivate  : self.onSiteChange,
                    onSiteDeactivate: self.onSiteChange,
                    onSiteDelete    : self.onSiteDelete,
                    onSiteSortSave  : self.onSiteChange,
                    onSiteLoad      : self.onSiteChange
                });
            });

            // projects events
            this.$Project.addEvents({
                onSiteCreate    : this.onSiteCreate,
                onSiteSave      : this.onSiteChange,
                onSiteActivate  : this.onSiteChange,
                onSiteDeactivate: this.onSiteChange,
                onSiteDelete    : this.onSiteDelete,
                onSiteSortSave  : this.onSiteChange,
                onSiteLoad      : this.onSiteChange
            });

            // copy and paste ids
            this.$cut  = false;
            this.$copy = false;
        },

        /**
         * Returns the qui/controls/sitemap/Map Control
         *
         * @method controls/projects/project/Sitemap#getMap
         * @return {Object} Binded Map Object (qui/controls/sitemap/Map)
         */
        getMap: function () {
            return this.$Map;
        },

        /**
         * Create the DOMNode of the sitemap
         *
         * @method controls/projects/project/Sitemap#create
         * @return {HTMLElement} Main DOM-Node Element
         */
        create: function () {
            if (this.$Elm) {
                return this.$Elm;
            }

            this.$Elm = this.$Map.create();

            return this.$Elm;
        },

        /**
         * Open the Map
         *
         * @method controls/projects/project/Sitemap#open
         */
        open: function () {
            if (!this.$Elm) {
                return;
            }

            var self = this;


            // if an specific id must be open
            if (typeof this.$openids !== 'undefined' && this.$Map.firstChild()) {
                var First = this.$Map.firstChild();

                if (First.isOpen()) {
                    this.fireEvent('openEnd', [First, this]);
                    return;
                }

                First.open();
                return;
            }


            this.$Map.clearChildren();

            if (this.getAttribute('id') === false) {
                this.$getFirstChild(function (result) {
                    self.$Map.clearChildren();

                    self.$addSitemapItem(
                        self.$Map,
                        self.$parseArrayToSitemapitem(result)
                    );

                    self.$Map.firstChild().open();

                    // media
                    if (self.getAttribute('media')) {
                        self.$Map.appendChild(
                            new QUISitemapItem({
                                text    : Locale.get(
                                    'quiqqer/system',
                                    'projects.project.sitemap.media'
                                ),
                                value   : 'media',
                                icon    : 'fa fa-picture-o icon-picture',
                                dragable: true,
                                events  : {
                                    onClick: function () {
                                        require(['controls/projects/project/Panel'], function (Panel) {
                                            new Panel().openMediaPanel(
                                                self.getAttribute('project')
                                            );
                                        });
                                    }
                                }
                            })
                        );
                    }
                });

                return;
            }

            this.$getSite(
                this.getAttribute('id'),
                function (result) {
                    self.$Map.clearChildren();

                    self.$addSitemapItem(
                        self.$Map,
                        self.$parseArrayToSitemapitem(result)
                    );

                    self.$Map.firstChild().open();
                }
            );
        },

        /**
         * Open the Sitemap to the specific id
         *
         * @method controls/projects/project/Sitemap#openSite
         * @param {Number} id - Site ID
         */
        openSite: function (id) {
            if (!this.$Map) {
                return;
            }

            var children = this.$Map.getChildrenByValue(id),
                self     = this;

            if (children.length) {
                children[0].select();
                return;
            }

            // if not exist, search the path
            Ajax.get('ajax_site_path', function (result, Request) {
                var items;
                var Map = self.getMap();

                result.push(Request.getAttribute('id'));

                items = Map.getChildrenByValue(result.getLast());

                if (items.length) {
                    items[0].select();
                    return;
                }

                var open_event = function () {
                    var i, id, len, items;

                    if (typeof self.$openids === 'undefined') {
                        return;
                    }

                    var ids = self.$openids,
                        Map = self.getMap();

                    for (i = 0, len = ids.length; i < len; i++) {
                        id    = ( ids[i] ).toInt();
                        items = Map.getChildrenByValue(id);

                        if (!items.length) {
                            // open parent
                            items = Map.getChildrenByValue(ids[i - 1]);

                            if (items.length && items[0].isOpen() === false) {
                                items[0].open();
                            }

                            return;
                        }

                        if (items[0].isOpen() === false) {
                            items[0].open();
                            return;
                        }
                    }

                    items[0].select();

                    delete self.$openids;

                    self.removeEvent('onOpenEnd', open_event);
                };

                self.$openids = result;
                self.addEvent('onOpenEnd', open_event);

                self.open();

            }, {
                project: this.$Project.encode(),
                id     : id
            });
        },

        /**
         * Get all selected Items
         *
         * @method controls/projects/project/Sitemap#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren: function () {
            return this.getMap().getSelectedChildren();
        },

        /**
         * Get specific children
         *
         * @method controls/projects/project/Sitemap#getChildren
         * @param {String} selector
         * @return {Array} List of children
         */
        getChildren: function (selector) {
            return this.getMap().getChildren(selector);
        },

        /**
         * Sitemap filter, the user can search for certain items
         *
         * @method qui/controls/sitemap/Map#search
         * @param {String} search
         * @return {Array} List of found elements
         */
        search: function (search) {
            return this.getMap().search(search);
        },

        /**
         * If no id, the sitemap starts from the first child of the project
         *
         * @method controls/projects/project/Sitemap#getFirstChild
         * @param {Function} callback - callback function
         * @private
         * @ignore
         */
        $getFirstChild: function (callback) {
            Ajax.get('ajax_project_firstchild', function (result) {
                if (typeof callback !== 'undefined') {
                    callback(result);
                }
            }, {
                project: this.$Project.encode()
            });
        },

        /**
         * Get the attributes from a site
         *
         * @method controls/projects/project/Sitemap#$getSite
         * @param {integer} id - Seiten ID
         * @param {Function} callback - call back function, if ajax is finish
         *
         * @private
         * @ignore
         */
        $getSite: function (id, callback) {
            Ajax.get('ajax_site_get', function (result) {
                if (typeof callback !== 'undefined') {
                    callback(result);
                }
            }, {
                project: this.$Project.encode(),
                id     : id
            });
        },

        /**
         * Load the Children asynchron
         *
         * @method controls/projects/project/Sitemap#$loadChildren
         * @param {Object} Item - (qui/controls/sitemap/Item) Parent sitemap item
         * @param {Function} [callback] - callback function, if ajax is finish
         *
         * @ignore
         */
        $loadChildren: function (Item, callback) {
            var self = this;

            Item.setAttribute('oicon', Item.getAttribute('icon'));
            Item.addIcon('icon-refresh icon-spin');
            Item.removeIcon(Item.getAttribute('icon'));

            Item.clearChildren();

            this.$Project.getConfig(function (config) {
                // limits
                var start        = 0,
                    limitStart   = Item.getAttribute('limitStart'),
                    projectLimit = 10;

                if ("adminSitemapMax" in config) {
                    projectLimit = parseInt(config.adminSitemapMax);
                }


                if (limitStart === false) {
                    limitStart = -1;
                }

                start = ( limitStart + 1 ) * projectLimit;

                // request
                Ajax.get('ajax_site_getchildren', function (result) {
                    var count    = ( result.count ).toInt(),
                        children = result.children,
                        end      = start + projectLimit,
                        sheets   = ( count / projectLimit ).ceil();

                    Item.setAttribute('hasChildren', count);
                    Item.clearChildren();

                    if (start > 0) {
                        Item.appendChild(
                            new QUISitemapItem({
                                icon       : 'icon-level-up',
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
                                icon       : 'icon-level-down',
                                text       : '...',
                                title      : 'Vor - Rechtsklick für weitere Optionen',
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

                    Item.addIcon(Item.getAttribute('oicon'));
                    Item.removeIcon('icon-refresh');


                    if (typeof callback === 'function') {
                        callback(Item);
                    }

                }, {
                    project: self.$Project.encode(),
                    id     : Item.getAttribute('value'),
                    params : JSON.encode({
                        attributes: 'id,name,title,has_children,nav_hide,linked,active,icon',
                        limit     : start + ',' + projectLimit
                    })
                });
            });
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method controls/projects/project/Sitemap#$parseArrayToSitemapitem
         * @param {Array} result
         * @param {Object} Itm - qui/controls/sitemap/Item
         * @return {Object} qui/controls/sitemap/Item
         *
         * @param {{name:string}} result
         * @param {{id:string}} result
         * @param {{title:string}} result
         * @param {{icon:string}} result
         * @param {{has_children:string}} result
         * @param {{nav_hide:string}} result
         * @param {{linked:string}} result
         * @param {{active:string}} result
         *
         * @private
         */
        $parseArrayToSitemapitem: function (result, Itm) {
            var self = this;

            if (typeof Itm === 'undefined') {
                Itm = new QUISitemapItem();
            }

            var attributes = {
                hasChildren: ( result.has_children ).toInt(),
                dragable   : true
            };

            if ("name" in result) {
                attributes.name = result.name;
                attributes.alt  = result.name + '.html';
            }

            if ("id" in result) {
                attributes.index = result.id;
                attributes.value = result.id;
            }

            if ("title" in result) {
                attributes.text  = result.title;
                attributes.title = result.title;
            }

            attributes.icon = 'fa fa-file-o icon-file-alt';

            if ("icon" in result) {
                attributes.icon = result.icon || 'fa fa-file-o icon-file-alt';
            }

            Itm.setAttributes(attributes);


            if ("nav_hide" in result) {
                if (result.nav_hide == '1') {
                    Itm.addIcon(URL_BIN_DIR + '16x16/navigation_hidden.png');
                } else {
                    Itm.removeIcon(URL_BIN_DIR + '16x16/navigation_hidden.png');
                }
            }

            if ("linked" in result) {
                if (result.linked == '1') {
                    Itm.setAttribute('linked', true);
                    Itm.addIcon(URL_BIN_DIR + '16x16/linked.png');
                } else {
                    Itm.setAttribute('linked', false);
                    Itm.removeIcon(URL_BIN_DIR + '16x16/linked.png');
                }
            }


            // Activ / Inactive
            var active = true;

            if ("active" in result) {
                if (result.active.toInt() === 0) {
                    Itm.deactivate();

                    active = false;
                } else {
                    Itm.activate();
                }
            }


            // contextmenu
            var ContextMenu = Itm.getContextMenu();

            ContextMenu.clearChildren()
                .appendChild(
                new QUIContextmenuItem({
                    name  : 'create-new-site',
                    text  : Locale.get('quiqqer/system', 'projects.project.site.btn.new.text'),
                    icon  : 'icon-file-text fa fa-file-text',
                    events: {
                        onClick: function () {
                            self.$createChild({
                                project: self.getAttribute('project'),
                                lang   : self.getAttribute('lang'),
                                id     : Itm.getAttribute('value')
                            });
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeperator()
            )
                .appendChild(
                new QUIContextmenuItem({
                    name  : 'copy',
                    text  : Locale.get('quiqqer/system', 'copy'),
                    icon  : 'icon-copy',
                    events: {
                        onClick: function () {
                            Clipboard.set({
                                project : self.getAttribute('project'),
                                lang    : self.getAttribute('lang'),
                                id      : Itm.getAttribute('value'),
                                Item    : Itm,
                                copyType: 'copy'
                            });
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'cut',
                    text  : Locale.get('quiqqer/system', 'cut'),
                    icon  : 'icon-cut',
                    events: {
                        onClick: function () {
                            Clipboard.set({
                                project : self.getAttribute('project'),
                                lang    : self.getAttribute('lang'),
                                id      : Itm.getAttribute('value'),
                                Item    : Itm,
                                copyType: 'cut'
                            });
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeperator()
            ).appendChild(
                new QUIContextmenuItem({
                    disabled: true,
                    name    : 'paste',
                    text    : Locale.get('quiqqer/system', 'paste'),
                    icon    : 'icon-paste',
                    events  : {
                        onClick: function () {
                            self.$pasteSite(Itm);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    disabled: true,
                    name    : 'linked-paste',
                    text    : Locale.get('quiqqer/system', 'linked.paste'),
                    icon    : 'icon-paste',
                    events  : {
                        onClick: function () {
                            self.$linkSite(Itm);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeperator()
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'de-activate-site',
                    text  : active ?
                        Locale.get('quiqqer/system', 'projects.project.site.btn.deactivate.text') :
                        Locale.get('quiqqer/system', 'projects.project.site.btn.activate.text'),
                    icon  : active ? 'icon-remove fa fa-remove' : 'icon-ok fa fa-ok',
                    events: {
                        onClick: function () {
                            if (active) {
                                self.$deactivateSite({
                                    project: self.getAttribute('project'),
                                    lang   : self.getAttribute('lang'),
                                    id     : Itm.getAttribute('value')
                                });
                            } else {
                                self.$activateSite({
                                    project: self.getAttribute('project'),
                                    lang   : self.getAttribute('lang'),
                                    id     : Itm.getAttribute('value')
                                });
                            }
                        }
                    }
                })
            );

            ContextMenu.addEvents({
                onShow: function (ContextMenu) {
                    Itm.highlight();

                    var data   = Clipboard.get(),
                        Paste  = ContextMenu.getChildren('paste'),
                        Linked = ContextMenu.getChildren('linked-paste'),
                        Cut    = ContextMenu.getChildren('cut');

                    Paste.disable();
                    Linked.disable();

                    if (Itm.getAttribute('value') == 1) {
                        Cut.disable();
                    } else {
                        Cut.enable();
                    }

                    if (!data) {
                        return;
                    }

                    if (typeof data.copyType === 'undefined') {
                        return;
                    }

                    if (data.copyType !== 'cut' && data.copyType !== 'copy') {
                        return;
                    }

                    var dataString = ' ' + data.project + ' (' + data.lang + ') ' +
                                     '#' + data.id + '';

                    Paste.setAttribute(
                        'text', Locale.get('quiqqer/system', 'paste') + dataString
                    );

                    Linked.setAttribute(
                        'text', Locale.get('quiqqer/system', 'linked.paste') + dataString
                    );

                    Paste.enable();
                    Linked.enable();

                    ContextMenu.resize();
                    ContextMenu.focus();
                },

                onBlur: function () {
                    Itm.deHighlight();
                }
            });

            return Itm;
        },

        /**
         * Add the item to its parent<br />
         * set the control attributes to the child item
         *
         * @method controls/projects/project/Sitemap#$addSitemapItem
         *
         * @param {Object} Parent - qui/controls/sitemap/Item
         * @param {Object} Child - qui/controls/sitemap/Item
         *
         * @private
         * @ignore
         */
        $addSitemapItem: function (Parent, Child) {
            Child.setAttribute('Control', this);
            Child.addEvent('onOpen', this.$open);
            Child.addEvent('onClose', this.$close);

            Parent.appendChild(Child);
        },

        /**
         * Opens the sheet window for an item
         *
         * @ignore
         * @param Item
         */
        $openSitemapItemSheetsWindow: function (Item) {
            if (!Item.getAttribute('sheets')) {
                return;
            }

            var self     = this,
                sheets   = ( Item.getAttribute('sheets') ).toInt(),
                Select   = new Element('select'),
                SiteItem = Item.getAttribute('Item');

            for (var i = 0, len = sheets; i < len; i++) {
                new Element('option', {
                    html : 'Blatt ' + ( i + 1 ),
                    value: i
                }).inject(Select);
            }

            if (SiteItem.getAttribute('limitStart') !== false) {
                Select.value = ( SiteItem.getAttribute('limitStart') ).toInt() + 1;
            }


            new QUIConfirm({
                title    : 'Blätterfunktion',
                maxHeight: 300,
                maxWidth : 500,
                events   : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();

                        Content.set({
                            html   : '<p>Welche Einträge der Seite sollen angezeigt werden?</p>',
                            'class': 'qui-projects-sitemap-sheetsWindow'
                        });

                        Select.inject(Content);
                    },

                    onSubmit: function (Win) {
                        var Select = Win.getContent().getElement('select'),
                            sheet  = ( Select.value ).toInt();

                        SiteItem.setAttribute('limitStart', sheet - 1);
                        self.$loadChildren(SiteItem);
                    }
                }
            }).open();
        },

        /**
         * Opens a Sitemap Item
         *
         * @method controls/projects/project/Sitemap#$open
         * @param {Object} Item - qui/controls/sitemap/Item
         *
         * @private
         * @ignore
         */
        $open: function (Item) {
            var self = this;

            this.fireEvent('openBegin', [Item, this]);

            this.$loadChildren(Item, function (Item) {
                self.fireEvent('openEnd', [Item, self]);
            });
        },

        /**
         * sitemap item close action
         *
         * @method controls/projects/project/Sitemap#$close
         * @param {Object} Item - qui/controls/sitemap/Item
         *
         * @private
         * @ignore
         */
        $close: function (Item) {
            Item.clearChildren();
        },

        /**
         * Move a site to a new parent
         *
         * @param {Object} NewParentItem - qui/controls/sitemap/Item
         */
        $pasteSite: function (NewParentItem) {
            var data = Clipboard.get();

            if (typeof data.Item === 'undefined') {
                return;
            }

            if (typeof data.project === 'undefined' ||
                typeof data.lang === 'undefined' ||
                typeof data.id === 'undefined') {
                return;
            }

            var self    = this,
                Project = Projects.get(data.project, data.lang),
                Site    = Project.get(data.id),
                Item    = data.Item;

            // move site
            if (data.copyType === 'cut') {
                if (this.getAttribute('project') != Site.getProject().getName()) {
                    return;
                }

                Site.move(NewParentItem.getAttribute('value'), function () {
                    Item.destroy();

                    if (!NewParentItem.isOpen()) {
                        NewParentItem.open();
                    } else {
                        self.$open(NewParentItem);
                    }
                });

                return;
            }

            var parentItemData = {
                parentId: NewParentItem.getAttribute('value'),
                project : {
                    name: this.getAttribute('project'),
                    lang: this.getAttribute('lang')
                }
            };

            Site.copy(parentItemData, function () {
                if (!NewParentItem.isOpen()) {
                    NewParentItem.open();
                } else {
                    self.$open(NewParentItem);
                }
            });
        },

        /**
         * Link a site into a new parent
         *
         * @param {Object} NewParentItem - qui/controls/sitemap/Item
         */
        $linkSite: function (NewParentItem) {
            var data = Clipboard.get();

            if (typeof data.Item === 'undefined') {
                return;
            }

            if (typeof data.project === 'undefined' ||
                typeof data.lang === 'undefined' ||
                typeof data.id === 'undefined') {
                return;
            }

            var self    = this,
                Project = Projects.get(data.project, data.lang),
                Site    = Project.get(data.id);

            Site.linked(NewParentItem.getAttribute('value'), function () {

                if (!NewParentItem.isOpen()) {
                    NewParentItem.open();
                } else {
                    self.$open(NewParentItem);
                }

            });
        },

        /**
         * Opens the child create confirm
         *
         * @param {Object} data - data.project, data.lang, data.id
         */
        $createChild: function (data) {
            var Project = Projects.get(data.project, data.lang),
                Site    = Project.get(data.id);

            if (Site.getAttribute('name')) {
                SiteUtils.openCreateChild(Site);
                return;
            }

            Site.load(function () {
                SiteUtils.openCreateChild(Site);
            });
        },

        /**
         * Acivate the site
         *
         * @param {Object} data - data.project, data.lang, data.id
         */
        $activateSite: function (data) {
            var Project = Projects.get(data.project, data.lang),
                Site    = Project.get(data.id);

            Site.activate();
        },

        /**
         * Acivate the site
         *
         * @param {Object} data - data.project, data.lang, data.id
         */
        $deactivateSite: function (data) {
            var Project = Projects.get(data.project, data.lang),
                Site    = Project.get(data.id);

            Site.deactivate();
        },

        /**
         * Site event handling - if a site has changes, the sitemap must change, too
         */

        /**
         * event - onSiteActivate. onSiteDeactivate, onSiteSave
         *
         * @param {Object} Project - (classes/projects/Project) Project of the Site that are changed
         * @param {Object} Site - (classes/projects/project/Site) Site that are changed
         */
        onSiteChange: function (Project, Site) {
            if (!this.$Map) {
                return;
            }

            var children = this.$Map.getChildrenByValue(Site.getId());

            if (!children.length) {
                return;
            }

            var i, len, Item, params;

            for (i = 0, len = children.length; i < len; i++) {
                Item   = children[i];
                params = Site.getAttributes();

                params.active       = Site.isActive();
                params.has_children = Site.hasChildren() ? 1 : 0;

                this.$parseArrayToSitemapitem(params, Item);

                if (Item.isOpen()) {
                    this.$open(Item);
                }
            }
        },

        /**
         * event - onSiteCreate
         *
         * @param {Object} Project - (classes/projects/Project) Project of the Site that are changed
         * @param {Object} Site - (classes/projects/project/Site) Site that create the child
         */
        onSiteCreate: function (Project, Site) {
            if (!this.$Map) {
                return;
            }

            // refresh the parent
            var children = this.$Map.getChildrenByValue(Site.getId());

            if (!children.length) {
                return;
            }

            var i, len, count;

            for (i = 0, len = children.length; i < len; i++) {
                if (children[i].isOpen()) {
                    this.$loadChildren(children[i]);
                    return;
                }

                count = children[i].getAttribute('hasChildren');

                if (typeOf(count) === 'number') {
                    count++;

                } else {
                    count = 1;
                }

                children[i].setAttribute('hasChildren', count);
                children[i].open();
            }
        },

        /**
         * event - on site delete
         *
         * @param {Object} Project - (classes/projects/Project) Project of the Site that are changed
         * @param {integer} siteid - siteid that are deleted
         */
        onSiteDelete: function (Project, siteid) {
            if (!this.$Map) {
                return;
            }

            var children = this.$Map.getChildrenByValue(siteid);

            if (!children.length) {
                return;
            }

            var i, len, Site, Parent;

            for (i = 0, len = children.length; i < len; i++) {
                // refresh parent item
                Parent = children[i].getParent();

                if (Parent) {
                    Site = this.$Project.get(Parent.getAttribute('value'));
                    Site.load();
                }

                children[i].destroy();
            }
        }
    });
});
