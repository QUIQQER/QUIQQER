/**
 * Displays a Sitemap from a project
 *
 * @module controls/projects/project/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/Sitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Separator',
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
        QUIContextmenuSeparator = arguments[4],
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
            'onSiteUnlink',

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
            multible : false, // multiple selection true or false -> @deprecated
            multiple : false  // multiple selection true or false
        },

        initialize: function (options) {
            this.parent(options);

            if (this.getAttribute('multible')) {
                this.setAttribute('multiple', this.getAttribute('multible'));
            }

            this.$Elm = null;
            this.$showNames = false;

            this.$Map = new QUISitemap({
                multiple: this.getAttribute('multiple')
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
                    onSiteUnlink    : self.onSiteUnlink,
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
                onSiteUnlink    : this.onSiteUnlink,
                onSiteSortSave  : this.onSiteChange,
                onSiteLoad      : this.onSiteChange
            });

            // copy and paste ids
            this.$cut = false;
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

            return self.$loadUsersSettings().then(function () {
                // if an specific id must be open
                if (typeof self.$openids !== 'undefined' && self.$Map.firstChild()) {
                    var First = self.$Map.firstChild();

                    if (First.isOpen()) {
                        self.fireEvent('openEnd', [
                            First,
                            self
                        ]);
                        return;
                    }

                    First.open();
                    return;
                }

                self.$Map.clearChildren();

                if (self.getAttribute('id') === false) {
                    self.$getFirstChild(function (result) {
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
                                    text    : Locale.get('quiqqer/core', 'projects.project.sitemap.media'),
                                    value   : 'media',
                                    icon    : 'fa fa-picture-o',
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

                self.$getSite(self.getAttribute('id')).then(function (result) {
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
                if (!result) {
                    return;
                }

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
                        id = parseInt(ids[i]);
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
         * @param {Function} [callback] - call back function, if ajax is finish
         *
         * @private
         * @ignore
         */
        $getSite: function (id, callback) {
            var self = this;

            return new Promise(function (resolve) {
                Ajax.get('ajax_site_get', function (result) {
                    if (typeof callback !== 'undefined') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    project: self.$Project.encode(),
                    id     : id
                });
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
            Item.addIcon('fa fa-spinner fa-spin');
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

                start = (limitStart + 1) * projectLimit;

                var Site = self.$Project.get(Item.getAttribute('value'));

                Site.getChildren({
                    attributes: 'id,name,title,has_children,nav_hide,linked,active,icon',
                    limit     : start + ',' + projectLimit
                }).then(function (result) {

                    var count    = parseInt(result.count),
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
                                title      : Locale.get('quiqqer/core', 'control.project.sitemap.prev'),
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
                                title      : Locale.get('quiqqer/core', 'control.project.sitemap.next'),
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
                    Item.removeIcon('fa-spinner');


                    if (typeof callback === 'function') {
                        callback(Item);
                    }
                });
            });
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method controls/projects/project/Sitemap#$parseArrayToSitemapitem
         * @param {Array} result
         * @param {Object} [Itm] - qui/controls/sitemap/Item
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
                hasChildren: parseInt(result.has_children),
                dragable   : true
            };

            if ("name" in result) {
                attributes.name = result.name;
                attributes.alt = result.name + '.html';
            }

            if ("id" in result) {
                attributes.index = result.id;
                attributes.value = result.id;
            }

            if ("title" in result) {
                attributes.text = this.$showNames ? result.name : result.title;
                attributes.title = this.$showNames ? result.name : result.title;
            }

            attributes.icon = 'fa fa-file-o';

            if ("icon" in result) {
                attributes.icon = result.icon || 'fa fa-file-o';
            }

            Itm.setAttributes(attributes);


            if ("nav_hide" in result) {
                if (parseInt(result.nav_hide) === 1) {
                    Itm.addIcon(URL_BIN_DIR + '16x16/navigation_hidden.png');
                } else {
                    Itm.removeIcon(URL_BIN_DIR + '16x16/navigation_hidden.png');
                }
            }

            if ("linked" in result) {
                if (parseInt(result.linked) === 1) {
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
                if (parseInt(result.active) === 0) {
                    Itm.deactivate();

                    active = false;
                } else {
                    Itm.activate();
                }
            }


            // contextmenu
            var ContextMenu = Itm.getContextMenu();

            ContextMenu.clearChildren().appendChild(
                new QUIContextmenuItem({
                    name  : 'create-new-site',
                    text  : Locale.get('quiqqer/core', 'projects.project.site.btn.new.text'),
                    icon  : 'fa fa-file-text',
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
                new QUIContextmenuSeparator()
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'copy',
                    text  : Locale.get('quiqqer/core', 'copy'),
                    icon  : 'fa fa-copy',
                    events: {
                        onClick: function () {
                            Clipboard.set({
                                project : self.getAttribute('project'),
                                lang    : self.getAttribute('lang'),
                                id      : Itm.getAttribute('value'),
                                site    : Itm.getAttribute('name'),
                                Item    : Itm,
                                copyType: 'copy'
                            });
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'cut',
                    text  : Locale.get('quiqqer/core', 'cut'),
                    icon  : 'fa fa-cut',
                    events: {
                        onClick: function () {
                            Clipboard.set({
                                project : self.getAttribute('project'),
                                lang    : self.getAttribute('lang'),
                                id      : Itm.getAttribute('value'),
                                site    : Itm.getAttribute('name'),
                                Item    : Itm,
                                copyType: 'cut'
                            });
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    disabled: true,
                    name    : 'paste',
                    text    : Locale.get('quiqqer/core', 'paste'),
                    icon    : 'fa fa-paste',
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
                    text    : Locale.get('quiqqer/core', 'linked.paste'),
                    icon    : 'fa fa-paste',
                    events  : {
                        onClick: function () {
                            self.$linkSite(Itm);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeparator()
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'open-in-website',
                    text  : Locale.get('quiqqer/core', 'project.sitemap.open.in.window'),
                    icon  : 'fa fa-external-link',
                    events: {
                        onClick: function () {
                            self.$openSiteInWebsite(Itm);
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuSeparator()
            ).appendChild(
                new QUIContextmenuItem({
                    name  : 'de-activate-site',
                    text  : active ?
                        Locale.get('quiqqer/core', 'projects.project.site.btn.deactivate.text') :
                        Locale.get('quiqqer/core', 'projects.project.site.btn.activate.text'),
                    icon  : active ? 'fa fa-remove' : 'fa fa-ok',
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

                    if (parseInt(Itm.getAttribute('value')) === 1) {
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

                    var dataString = ' ' + data.site + ' (' + data.lang + ') ' +
                                     '#' + data.id + '';

                    Paste.setAttribute(
                        'text',
                        Locale.get('quiqqer/core', 'paste') + ':<br /> ' + dataString
                    );

                    Linked.setAttribute(
                        'text',
                        Locale.get('quiqqer/core', 'linked.paste') + ':<br /> ' + dataString
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
                sheets   = parseInt(Item.getAttribute('sheets')),
                Select   = new Element('select'),
                SiteItem = Item.getAttribute('Item');

            for (var i = 0, len = sheets; i < len; i++) {
                new Element('option', {
                    html : 'Blatt ' + (i + 1),
                    value: i
                }).inject(Select);
            }

            if (SiteItem.getAttribute('limitStart') !== false) {
                Select.value = parseInt(SiteItem.getAttribute('limitStart')) + 1;
            }


            new QUIConfirm({
                title    : Locale.get('quiqqer/core', 'control.project.sitemap.sheetWindow.title'),
                maxHeight: 300,
                maxWidth : 500,
                events   : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();

                        Content.set({
                            html   : Locale.get('quiqqer/core', 'control.project.sitemap.sheetWindow.content'),
                            'class': 'qui-projects-sitemap-sheetsWindow'
                        });

                        Select.inject(Content);
                    },

                    onSubmit: function (Win) {
                        var Select = Win.getContent().getElement('select'),
                            sheet  = parseInt(Select.value);

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

            this.fireEvent('openBegin', [
                Item,
                this
            ]);

            this.$loadChildren(Item, function (Item) {
                self.fireEvent('openEnd', [
                    Item,
                    self
                ]);
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
                if (this.getAttribute('project') !== Site.getProject().getName()) {
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
         * Opens the site in the website
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $openSiteInWebsite: function (Item) {
            SiteUtils.openSite(
                this.$Project.getName(),
                this.$Project.getLang(),
                Item.getAttribute('value')
            );
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
                Item = children[i];
                params = Site.getAttributes();

                params.active = Site.isActive();
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
        },

        /**
         * event - on site unlink
         *
         * @param Project
         * @param Site
         * @param parentId
         */
        onSiteUnlink: function (Project, Site, parentId) {
            if (!this.$Map) {
                return;
            }

            var children = this.$Map.getChildrenByValue(Site.getId());

            if (!children.length) {
                return;
            }

            children.each(function (MapEntry) {
                var Parent = MapEntry.getParent();

                if (!Parent) {
                    return;
                }

                if (Parent.getAttribute('value') == parentId) {
                    MapEntry.destroy();
                }

                this.$Project.get(Parent.getAttribute('value')).load();
            }.bind(this));
        },

        /**
         * Load the user settings
         * eq: quiqqer.sitemap.showNames
         *
         * @return {Promise}
         */
        $loadUsersSettings: function () {
            var self = this;

            return new Promise(function (resolve) {
                require(['Users'], function (Users) {
                    var CurrentUser = Users.getUserBySession();

                    if (CurrentUser.isLoaded()) {
                        self.$showNames = CurrentUser.getAttribute('quiqqer.sitemap.showNames');
                        resolve();
                        return;
                    }

                    CurrentUser.load().then(function () {
                        self.$showNames = CurrentUser.getAttribute('quiqqer.sitemap.showNames');
                        resolve();
                    });
                });
            });
        }
    });
});
