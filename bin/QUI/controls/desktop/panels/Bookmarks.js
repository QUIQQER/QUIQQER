/**
 * QUIQQER Bookmars
 *
 * @module controls/desktop/panels/Bookmarks
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/bookmarks/Panel
 * @require classes/utils/Sortables
 * @require css!controls/desktop/panels/Bookmarks.css
 */
define('controls/desktop/panels/Bookmarks', [

    'qui/QUI',
    'qui/controls/bookmarks/Panel',
    'classes/utils/Sortables',
    'qui/controls/windows/Confirm',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Ajax',
    'Locale',
    'utils/Panels',

    'css!controls/desktop/panels/Bookmarks.css'

], function (QUI, QUIBookmarks, Sortables, QUIConfirm, QUISitemap, QUISitemapItem, QUIAjax, QUILocale, PanelUtils) {
    "use strict";

    return new Class({

        Extends: QUIBookmarks,
        Type   : 'controls/desktop/panels/Bookmarks',

        Binds: [
            '$onCreate',
            'openAddDialog',
            'enableEdit',
            'enableSorting'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate
            });
        },

        /**
         * event: on create
         */
        $onCreate: function () {
            var self = this;

            this.setAttributes({
                title : QUILocale.get('quiqqer/system', 'panels.bookmarks.title'),
                icon  : 'fa fa-bookmark',
                footer: false
            });

            this.addButton({
                name  : 'add',
                icon  : 'fa fa-plus',
                title : QUILocale.get('quiqqer/quiqqer', 'control.bookmarks.panel.button.add.title'),
                events: {
                    onClick: this.openAddDialog
                }
            });

            this.addButton({
                name  : 'edit',
                icon  : 'fa fa-edit',
                title : QUILocale.get('quiqqer/quiqqer', 'control.bookmarks.panel.button.edit.title'),
                events: {
                    onClick: function (Btn) {
                        if (Btn.isActive()) {
                            Btn.setNormal();
                            return;
                        }

                        Btn.setActive();
                    },

                    onActive: function () {
                        self.enableEdit();
                    },

                    onNormal: function () {
                        self.disableEdit();
                    }
                },
                styles: {
                    'float': 'right'
                }
            });

            this.addButton({
                name: 'separator',
                type: 'separator'
            });

            this.addButton({
                name  : 'sort',
                title : QUILocale.get('quiqqer/quiqqer', 'control.bookmarks.panel.button.sort.title'),
                icon  : 'fa fa-sort',
                styles: {
                    'float': 'right'
                },
                events: {
                    onClick: function (Btn) {
                        if (Btn.isActive()) {
                            Btn.setNormal();
                            return;
                        }

                        Btn.setActive();
                    },

                    onActive: function () {
                        self.enableSorting();
                    },

                    onNormal: function () {
                        self.disableSorting();
                    }
                }
            });

            this.getButtons('separator').getElm().setStyle('float', 'right');
        },

        /**
         * Booksmarks not editable
         */
        fix: function () {
            this.$fixed = true;
        },

        /**
         * Booksmarks are editable
         */
        unfix: function () {
            this.$fixed = false;
        },

        /**
         * enable the bookmark edit
         */
        enableEdit: function () {
            this.getButtons('sort').setNormal();

            var i, len, text, Elm;
            var bookmarks = this.$Container.getElements('.qui-bookmark-text');

            for (i = 0, len = bookmarks.length; i < len; i++) {
                Elm  = bookmarks[i];
                text = bookmarks[i].get('text');

                Elm.set('html', '');

                new Element('input', {
                    value : text,
                    styles: {
                        width: '100%'
                    }
                }).inject(Elm);
            }

            this.setAttribute('dragable', true);
            this.unfix();
        },

        /**
         * disable sorting
         */
        disableEdit: function () {
            var i, len, text;
            var fields = this.$Container.getElements('.qui-bookmark-text input');

            for (i = 0, len = fields.length; i < len; i++) {
                text = fields[i].value;
                fields[i].getParent().set('html', text);
            }

            this.setAttribute('dragable', false);
            this.fix();
        },

        /**
         * enable sorting
         */
        enableSorting: function () {
            var self = this;
            var List = this.$Elm.getElements('.qui-bookmark');

            this.$Container.addClass('qui-bookmark-list');
            this.getButtons('edit').setNormal();

            // set placeholder divs
            List.each(function (Child) {
                new Element('div', {
                    'class': 'qui-bookmark-placeholder',
                    html   : '<span class="fa fa-arrows"></span>' +
                    Child.getElement('.qui-bookmark-text').get('text')
                }).inject(Child);
            });

            // dragdrop sort
            this.$Sortables = new Sortables(this.$Container, {
                handles: List,
                revert : {
                    duration  : 500,
                    transition: 'elastic:out'
                },
                clone  : function (event) {
                    var Target = event.target;

                    if (!Target.hasClass('.qui-bookmark')) {
                        Target = Target.getParent('.qui-bookmark');
                    }

                    var size = Target.getSize(),
                        pos  = Target.getPosition(self.$Container);

                    Target.addClass('qui-bookmark-active');

                    return new Element('div', {
                        styles: {
                            background: 'rgba(0,0,0,0.3)',
                            height    : size.y,
                            position  : 'absolute',
                            top       : pos.y,
                            width     : size.x,
                            zIndex    : 1000
                        }
                    });
                },

                onStart: function () {
                    self.$Container.addClass('qui-bookmark-dd-active');

                    self.$Container.getElements('.qui-bookmark-placeholder')
                        .set('display', 'none');

                    self.$Container.setStyles({
                        height  : self.$Container.getSize().y,
                        overflow: 'hidden',
                        width   : self.$Container.getSize().x
                    });
                },

                onComplete: function () {
                    self.$Container.removeClass('qui-bookmark-dd-active');

                    self.$Container.getElements('.qui-bookmark-active')
                        .removeClass('qui-bookmark-active');

                    self.$Container.getElements('.qui-bookmark-placeholder')
                        .set('display', null);

                    self.$Container.setStyles({
                        height  : null,
                        overflow: null,
                        width   : null
                    });
                }
            });
        },

        /**
         * disable sorting
         */
        disableSorting: function () {
            this.$Container.removeClass('qui-bookmark-list');
            this.$Container.getElements('.qui-bookmark-placeholder').destroy();

            if (typeof this.$Sortables !== 'undefined') {
                if (this.$Sortables && "detach" in this.$Sortables) {
                    this.$Sortables.detach();
                }

                this.$Sortables = null;
            }
        },

        /**
         * overwrite appendChild, because we must use some special click events
         */
        appendChild: function (Item) {
            if (!this.$Container) {
                return this;
            }

            var Child;

            if (typeOf(Item) === 'object') {
                var json = JSON.encode(Item);

                Child = this.$createEntry({
                    text : Item.text,
                    icon : Item.icon,
                    path : '',
                    click: 'BookmarkPanel.$itemClick(' + json + ')'
                }).inject(this.$Container);

            } else if (Item.getType() === 'qui/controls/contextmenu/Item') {
                var path    = Item.getPath(),
                    xmlFile = Item.getAttribute('qui-xml-file');

                if (xmlFile) {
                    path = 'xmlFile:' + xmlFile;
                }

                Child = this.$createEntry({
                    text : Item.getAttribute('text'),
                    icon : Item.getAttribute('icon'),
                    path : path,
                    click: 'BookmarkPanel.xmlMenuClick(path)'
                }).inject(this.$Container);

            } else if (Item.getType() === 'qui/controls/sitemap/Item') {
                var ProjectSitemap = Item.getMap().getParent(),

                    project        = ProjectSitemap.getAttribute('project'),
                    lang           = ProjectSitemap.getAttribute('lang'),
                    value          = Item.getAttribute('value');

                var click = 'require(["utils/Panels"], function(U) { U.openSitePanel( "' + project + '", "' + lang + '", "' + value + '" ) })',
                    text  = Item.getAttribute('text');

                if (value === 'media') {
                    click = 'require(["utils/Panels"], function(U) { U.openMediaPanel( "' + project + '" ) })';
                    text  = Item.getAttribute('text') + ' (' + project + ')';
                }

                Child = this.$createEntry({
                    text : text,
                    icon : Item.getAttribute('icon'),
                    click: click,
                    path : ''
                }).inject(this.$Container);

            } else {
                Child = this.$createEntry({
                    text : Item.getAttribute('text'),
                    icon : Item.getAttribute('icon'),
                    click: Item.getAttribute('bookmark'),
                    path : ''
                }).inject(this.$Container);
            }

            this.$bookmarks.push(Child);

            this.fireEvent('appendChild', [this, Child]);

            return this;
        },

        /**
         * XML menu click
         * @param {String} path - path of the xml file eq: xmlFile:path/settings.xml
         */
        xmlMenuClick: function (path) {
            if (path.match('xmlFile:')) {
                require([
                    'Menu',
                    'controls/desktop/panels/XML'
                ], function (Menu, XMLPanel) {
                    Menu.openPanelInTasks(
                        new XMLPanel(path.substr(8))
                    );
                });
            }
        },

        /**
         * item click for an object entry
         *
         * @param data
         */
        $itemClick: function (data) {
            if (typeOf(data) !== 'object') {
                return;
            }

            if (!data.hasOwnProperty('require')) {
                return;
            }

            require([
                data.require,
                'qui/controls/desktop/Panel',
                'qui/controls/windows/Popup'
            ], function (Cls, QUIPanel, QUIPopup) {
                var Instance = new Cls();
                if (Instance instanceof QUIPanel) {
                    PanelUtils.openPanelInTasks(Instance);
                    return;
                }

                if (Instance instanceof QUIPopup) {
                    Instance.open();
                }
            });
        },

        /**
         * make a click on a menu item by path
         *
         * @param {String} path - Path to the menu item
         * @return {Boolean}
         */
        $clickMenuItem: function (path) {
            if (this.$fixed === false) {
                return true;
            }

            return this.parent(path);
        },

        /**
         * Set all buttons to normal status
         */
        $normalizeButtons: function () {
            this.getButtonBar().getChildren().each(function (Btn) {
                Btn.setNormal();
            });
        },

        /**
         * Opens the add dialog
         * User can add a bookmark
         */
        openAddDialog: function () {
            var self = this;

            new QUIConfirm({
                icon     : 'fa fa-bookmarks',
                title    : 'Bookmarks',
                maxWidth : 500,
                maxHeight: 700,
                events   : {
                    onOpen: function (Win) {
                        Win.getContent().set('html', '');
                        Win.Loader.show();

                        QUIAjax.get('ajax_menu', function (menu) {
                            var getItems = function (items) {
                                var i, len, item, children;
                                var result = [];

                                for (i = 0, len = items.length; i < len; i++) {
                                    item = items[i];

                                    if (item.items.length) {
                                        children = getItems(item.items);
                                        result.combine(children);
                                    }

                                    if (!item.hasOwnProperty('require')) {
                                        continue;
                                    }

                                    if (item.require !== '') {
                                        result.push(item);
                                    }
                                }

                                return result;
                            };

                            var items   = getItems(menu);
                            var Sitemap = new QUISitemap({
                                multiple: true,
                                styles  : {
                                    margin : '10px 0px',
                                    padding: 0
                                }
                            }).inject(Win.getContent());

                            items.sort(function (a, b) {
                                var nameA = a.text.toUpperCase();
                                var nameB = b.text.toUpperCase();

                                if (nameA < nameB) {
                                    return -1;
                                }
                                if (nameA > nameB) {
                                    return 1;
                                }

                                return 0;
                            });

                            for (var i = 0, len = items.length; i < len; i++) {
                                Sitemap.appendChild(
                                    new QUISitemapItem({
                                        icon: items[i].icon,
                                        name: items[i].name,
                                        text: items[i].text,
                                        data: items[i]
                                    })
                                );
                            }

                            Win.Loader.hide();
                        });
                    },

                    onSubmit: function (Win) {
                        Win.Loader.show();

                        var Map = Win.getContent()
                                     .getElement('[data-qui="qui/controls/sitemap/Map"]');

                        var Sitemap = QUI.Controls.getById(Map.get('data-quiid'));
                        var items   = Sitemap.getSelectedChildren()
                                             .map(function (SitemapItem) {
                                                 return SitemapItem.getAttribute('data');
                                             });

                        for (var i = 0, len = items.length; i < len; i++) {
                            self.appendChild(items[i]);
                        }
                    }
                }
            }).open();
        }
    });
});
