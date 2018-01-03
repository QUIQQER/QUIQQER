/**
 * QUIQQER Main Menu
 *
 * @module controls/menu/Manager
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/menu/Manager', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/contextmenu/Bar',
    'qui/controls/contextmenu/BarItem',
    'qui/controls/contextmenu/Item',
    'qui/controls/desktop/Panel',
    'Ajax',
    'Locale',
    'utils/Panels',

    'css!controls/menu/Manager.css'

], function (QUI, Control, ContextmenuBar, ContextmenuBarItem, ContextmenuItem, Panel, Ajax, QUILocale, PanelUtils) {
    "use strict";

    return new Class({

        Extends: Control,
        Type   : 'controls/menu/Manager',

        initialize: function (options) {
            this.parent(options);

            this.$Bar      = null;
            this.$Profile  = null;
            this.$isLoaded = false;
        },

        /**
         * Create the topic menu
         *
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            QUILocale.setCurrent(USER.lang);

            this.$Bar = new ContextmenuBar({
                dragable: true,
                name    : 'quiqqer-menu-bar'
            });

            this.$Bar.setParent(this);

            Ajax.get('ajax_menu', function (result) {
                self.$Bar.insert(result);
                self.$Bar.getChildren().each(function (BarItem) {
                    if (BarItem.getAttribute('name') !== 'quiqqer') {
                        BarItem.setAttribute('hideifempty', true);
                    }

                    if (BarItem.getAttribute('name') === 'profile') {
                        self.$Profile = BarItem;
                        BarItem.getElm().setStyle('display', 'none');
                    }
                });

                result.each(function (entry, i) {
                    var Child = self.$Bar.getElm().getChildren(
                        'div:nth-child(' + (i + 1) + ')'
                    );

                    if ("name" in entry) {
                        Child.set('data-name', entry.name);
                    }
                });

                self.$renderProfile();

                self.$isLoaded = true;
                self.fireEvent('menuLoaded');
            });

            return this.$Bar.create();
        },

        /**
         * Is the menu loaded?
         *
         * @return {Boolean}
         */
        isLoaded: function () {
            return this.$isLoaded;
        },

        /**
         * Return the ContextBar
         *
         * @return {Object} qui/controls/contextmenu/Bar
         */
        getChildren: function () {
            return this.$Bar;
        },

        /**
         * render the profile items
         */
        $renderProfile: function () {
            var self   = this,
                Menu   = document.getElement('.qui-menu-container'),
                letter = USER.name.substr(0, 1);

            var ContextMenu = self.$Profile.getContextMenu();

            ContextMenu.addEvent('blur', function () {
                ContextMenu.hide.delay(200, ContextMenu);
            });

            ContextMenu.setTitle(window.USER.username);
            ContextMenu.getElm().addClass('qui-profile-contextMenu');

            require([
                'qui/controls/contextmenu/Item',
                'qui/controls/contextmenu/Separator',
                'Locale'
            ].concat(QUIQQER_LOCALE), function (Item, Separator) {
                ContextMenu.appendChild(new Separator());

                ContextMenu.appendChild(
                    new Item({
                        icon  : 'fa fa-power-off',
                        text  : QUILocale.get('quiqqer/quiqqer', 'menu.log.out'),
                        events: {
                            onClick: function () {
                                window.logout();
                            }
                        }
                    })
                );
            });

            var Profile = new Element('div', {
                'class': 'qui-contextmenu-baritem smooth qui-profile-button',
                events : {
                    click: function (event) {
                        event.stop();

                        var Menu    = self.$Profile.getContextMenu(),
                            MenuElm = Menu.getElm();

                        Menu.setPosition(
                            this.getPosition().x,
                            70
                        );

                        Menu.setAttribute('corner', 'topRight');
                        Menu.getElm().inject(document.body);
                        Menu.show();
                        MenuElm.setStyle('left', parseInt(MenuElm.getStyle('left')) + 40);

                        // ff blur focus workaround
                        (function () {
                            Menu.focus();
                        }).delay(200);
                    }
                }
            }).inject(Menu);

            var LetterElm = new Element('span', {
                html   : letter,
                'class': 'qui-profile-button-letter'
            }).inject(Profile);

            new Element('span', {
                html  : '<span class="fa fa-angle-down"></span>',
                styles: {
                    position: 'absolute',
                    right   : -15,
                    top     : 0
                }
            }).inject(Profile);

            if (window.USER.avatar !== '') {
                Profile.setStyle('background-image', "url('" + window.USER.avatar + "')");
                LetterElm.destroy();
            } else {
                Profile.addClass('qui-profile-button-' + letter.toLowerCase());
            }
        },

        /**
         * Menu click helper method
         *
         * @param {Object} Item - (qui/controls/contextmenu/Item) Menu Item
         * @param {Event} event
         */
        menuClick: function (Item, event) {
            var i, len, list;

            if (typeOf(event) === 'domevent') {
                event.stop();
            }

            var self        = this,
                menuRequire = Item.getAttribute('require'),
                exec        = Item.getAttribute('exec'),
                xmlFile     = Item.getAttribute('qui-xml-file');

            Item.setAttribute('originalIcon', Item.getAttribute('icon'));
            Item.setAttribute('icon', 'fa fa-spinner fa-spin');

            // js require
            if (menuRequire) {
                this.$menuRequire(Item).then(function () {
                    Item.setAttribute('icon', Item.getAttribute('originalIcon'));
                });

                return;
            }

            // xml setting file
            if (xmlFile) {
                // panel still exists?
                list = QUI.Controls.getByType('controls/desktop/panels/XML');

                for (i = 0, len = list.length; i < len; i++) {
                    if (list[i].getFile() === xmlFile) {
                        // if a task exist, click it and open the instance
                        var Task = list[i].getAttribute('Task');

                        if (Task && Task.getType() === 'qui/controls/taskbar/Task') {
                            list[i].getAttribute('Task').click();
                            Item.setAttribute('icon', Item.getAttribute('originalIcon'));
                            return;
                        }

                        list[i].open();
                        Item.setAttribute('icon', Item.getAttribute('originalIcon'));
                        return;
                    }
                }

                require(['controls/desktop/panels/XML'], function (XMLPanel) {
                    self.openPanelInTasks(
                        new XMLPanel(xmlFile)
                    );

                    Item.setAttribute('icon', Item.getAttribute('originalIcon'));
                });

                return;
            }

            if (exec === '') {
                Item.setAttribute('icon', Item.getAttribute('originalIcon'));
                Item.$onMouseEnter.delay(10, this); // workaround, menu don't hide
                return;
            }

            // js function
            try {
                eval(exec);
            } catch (e) {
                QUI.getMessageHandler(function (MessageHandler) {
                    MessageHandler.addError(e);
                });
            }

            Item.setAttribute('icon', Item.getAttribute('originalIcon'));
        },

        /**
         * It method has a require option
         *
         * @param {Object} Item - (qui/controls/contextmenu/Item)
         * @return {Promise}
         */
        $menuRequire: function (Item) {
            var i, len;

            var menuRequire = Item.getAttribute('require'),
                list        = QUI.Controls.getByType(menuRequire);

            var attributes = Object.merge(
                Item.getStorageAttributes(),
                Item.getAttributes()
            );

            if (Item.getAttribute('originalIcon')) {
                attributes.icon = Item.getAttribute('originalIcon');
            }

            return new Promise(function (resolve) {
                if (!list.length) {
                    this.$createControl(menuRequire, attributes).then(resolve);
                    return;
                }

                if (menuRequire === 'controls/projects/project/Settings') {
                    for (i = 0, len = list.length; i < len; i++) {
                        if (list[i].getAttribute('project') === attributes.project) {
                            PanelUtils.execPanelOpen(list[0]);
                            return;
                        }
                    }

                    this.$createControl(menuRequire, attributes).then(resolve);
                    return;
                }

                if (instanceOf(list[0], Panel)) {
                    PanelUtils.execPanelOpen(list[0]);
                } else {
                    list[0].open();
                }

                resolve();
            }.bind(this));
        },

        /**
         * Create the control and opened it
         *
         * @param {String} controlName - require of the control -> eq: controls/projects/project/Settings
         * @param {Object} attributes - attributes of the control
         * @return {Promise}
         */
        $createControl: function (controlName, attributes) {
            var self = this;

            return new Promise(function (resolve, reject) {
                require([controlName], function (Control) {
                    var Ctrl = new Control(attributes);

                    if (instanceOf(Ctrl, Panel)) {
                        self.openPanelInTasks(Ctrl);
                        resolve();
                        return;
                    }

                    Ctrl.open();
                    resolve();
                }, reject);
            });
        },

        /**
         * Open a Panel in a taskpanel
         */
        openPanelInTasks: function (Panel) {
            PanelUtils.openPanelInTasks(Panel);
        }
    });
});
