
/**
 * QUIQQER Bookmars
 *
 * @module controls/desktop/panels/Bookmarks
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/desktop/panels/Bookmarks', [

    'qui/controls/bookmarks/Panel',
    'classes/utils/Sortables',

    'css!controls/desktop/panels/Bookmarks.css'

], function (QUIBookmarks, Sortables) {
    "use strict";

    return new Class({

        Extends : QUIBookmarks,
        Type    : 'controls/desktop/panels/Bookmarks',

        /**
         * Booksmarks not editable
         */
        fix : function () {
            this.$fixed = true;

            this.$normalizeButtons();
            this.getButtonBar().clear();
        },

        /**
         * Booksmarks are editable
         */
        unfix : function () {
            var self = this;

            this.$fixed = false;

            this.addButton({
                name      : 'sort',
                text      : 'Sortierung',
                textimage : 'icon-sort',
                events :
                {
                    onClick : function (Btn) {
                        if (Btn.isActive()) {
                            self.$normalizeButtons();
                            return;
                        }

                        self.$normalizeButtons();
                        Btn.setActive();
                    },

                    onActive : function () {
                        self.enableSorting();
                    },

                    onNormal : function () {
                        self.disableSorting();
                    }
                }
            });


            this.addButton({
                name      : 'edit',
                text      : 'Editieren',
                textimage : 'icon-edit',
                events :
                {
                    onClick : function (Btn) {
                        if (Btn.isActive()) {
                            self.$normalizeButtons();
                            return;
                        }

                        self.$normalizeButtons();
                        Btn.setActive();
                    },

                    onActive : function () {
                        self.enableEdit();
                    },

                    onNormal : function () {
                        self.disableEdit();
                    }
                }
            });
        },

        /**
         *
         */
        enableEdit : function () {
            var i, len, text, Elm;
            var bookmarks = this.$Container.getElements('.qui-bookmark-text');

            for (i = 0, len = bookmarks.length; i < len; i++) {
                Elm  = bookmarks[ i ];
                text = bookmarks[ i ].get('text');

                Elm.set('html', '');

                new Element('input', {
                    value  : text,
                    styles : {
                        width: '100%'
                    }
                }).inject(Elm);
            }
        },

        /**
         * disable sorting
         */
        disableEdit : function () {
            var i, len, text;
            var fields = this.$Container.getElements('.qui-bookmark-text input');

            for (i = 0, len = fields.length; i < len; i++) {
                text = fields[ i ].value;
                fields[ i].getParent().set('html', text);
            }
        },

        /**
         * enable sorting
         */
        enableSorting : function () {
            var self = this;
            var List = this.$Elm.getElements('.qui-bookmark');

            this.$Container.addClass('qui-bookmark-list');

            // set placeholder divs
            List.each(function (Child) {
                new Element('div', {
                    'class' : 'qui-bookmark-placeholder',
                    html    : '<span class="fa fa-arrows"></span>' +
                    Child.getElement('.qui-bookmark-text').get('text')
                }).inject(Child);
            });

            // dragdrop sort
            this.$Sortables = new Sortables(this.$Container, {
                handles: List,
                revert: {
                    duration   : 500,
                    transition : 'elastic:out'
                },
                clone : function (event) {
                    var Target = event.target;

                    if (!Target.hasClass('.qui-bookmark')) {
                        Target = Target.getParent('.qui-bookmark');
                    }

                    var size = Target.getSize(),
                        pos  = Target.getPosition(self.$Container);

                    Target.addClass('qui-bookmark-active');

                    return new Element('div', {
                        styles : {
                            background : 'rgba(0,0,0,0.3)',
                            height     : size.y,
                            top        : pos.y,
                            width      : size.x,
                            zIndex     : 1000
                        }
                    });
                },

                onStart : function () {
                    self.$Container.addClass('qui-bookmark-dd-active');

                    self.$Container.getElements('.qui-bookmark-placeholder')
                        .set('display', 'none');

                    self.$Container.setStyles({
                        height   : self.$Container.getSize().y,
                        overflow : 'hidden',
                        width    : self.$Container.getSize().x
                    });
                },

                onComplete : function () {
                    self.$Container.removeClass('qui-bookmark-dd-active');

                    self.$Container.getElements('.qui-bookmark-active')
                        .removeClass('qui-bookmark-active');

                    self.$Container.getElements('.qui-bookmark-placeholder')
                        .set('display', null);

                    self.$Container.setStyles({
                        height   : null,
                        overflow : null,
                        width    : null
                    });
                }
            });
        },

        /**
         * disable sorting
         */
        disableSorting : function () {
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
        appendChild : function (Item) {
            if (!this.$Container) {
                return this;
            }

            var Child;

            // parse qui/controls/contextmenu/Item to an Bookmark
            if (Item.getType() == 'qui/controls/contextmenu/Item') {
                var path    = Item.getPath(),
                    xmlFile = Item.getAttribute('qui-xml-file');

                if (xmlFile) {
                    path = 'xmlFile:' + xmlFile;
                }

                Child = this.$createEntry({
                    text  : Item.getAttribute('text'),
                    icon  : Item.getAttribute('icon'),
                    path  : path,
                    click : 'BookmarkPanel.xmlMenuClick(path)'
                }).inject(this.$Container);

            } else if (Item.getType() == 'qui/controls/sitemap/Item') {
                var ProjectSitemap = Item.getMap().getParent(),

                    project = ProjectSitemap.getAttribute('project'),
                    lang    = ProjectSitemap.getAttribute('lang'),
                    value   = Item.getAttribute('value');

                var click = 'require(["utils/Panels"], function(U) { U.openSitePanel( "' + project + '", "' + lang + '", "' + value + '" ) })',
                    text  = Item.getAttribute('text');

                if (value === 'media') {
                    click = 'require(["utils/Panels"], function(U) { U.openMediaPanel( "' + project + '" ) })';
                    text  = Item.getAttribute('text') + ' (' + project + ')';
                }

                Child = this.$createEntry({
                    text  : text,
                    icon  : Item.getAttribute('icon'),
                    click : click,
                    path  : ''
                }).inject(this.$Container);

            } else {
                Child = this.$createEntry({
                    text  : Item.getAttribute('text'),
                    icon  : Item.getAttribute('icon'),
                    click : Item.getAttribute('bookmark'),
                    path  : ''
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
        xmlMenuClick : function (path) {
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
         * make a click on a menu item by path
         *
         * @param {String} path - Path to the menu item
         * @return {Boolean}
         */
        $clickMenuItem : function (path) {
            if (this.$fixed === false) {
                return true;
            }

            return this.parent(path);
        },

        /**
         * Set all buttons to normal status
         */
        $normalizeButtons : function () {
            this.getButtonBar().getChildren().each(function (Btn) {
                Btn.setNormal();
            });
        }
    });
});
