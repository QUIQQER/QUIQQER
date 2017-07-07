/**
 * QUIQQER Main Menu
 *
 * @module controls/menu/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/contextmenu/Bar
 * @require qui/controls/contextmenu/BarItem
 * @require qui/controls/contextmenu/Item
 * @require qui/controls/desktop/Panel
 * @require Ajax
 * @require utils/Panels
 */
define('controls/menu/Manager', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/contextmenu/Bar',
    'qui/controls/contextmenu/BarItem',
    'qui/controls/contextmenu/Item',
    'qui/controls/desktop/Panel',
    'Ajax',
    'utils/Panels'

], function (QUI, Control, ContextmenuBar, ContextmenuBarItem, ContextmenuItem, Panel, Ajax, PanelUtils) {
    "use strict";

    return new Class({

        Extends: Control,
        Type   : 'controls/menu/Manager',

        initialize: function (options) {
            this.$Bar = null;
            this.parent(options);

            this.$isLoaded = false;
        },

        /**
         * Create the topic menu
         *
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

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
                });

                result.each(function (entry, i) {
                    var Child = self.$Bar.getElm().getChildren(
                        'div:nth-child(' + (i + 1) + ')'
                    );

                    if ("name" in entry) {
                        Child.set('data-name', entry.name);
                    }
                });

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
         * Menu click helper method
         *
         * @param {Object} Item - (qui/controls/contextmenu/Item) Menu Item
         */
        menuClick: function (Item) {
            var i, len, list;

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

            // js function
            try {
                eval(exec);

            } catch (e) {
                QUI.getMessageHandler(function (MessageHandler) {
                    MessageHandler.addError(e);
                });
            }
        },

        /**
         * It method has a require option
         *
         * @param {Object} Item - (qui/controls/contextmenu/Item)
         * @return {Promise}
         */
        $menuRequire: function (Item) {
            var i, len, list;

            var menuRequire = Item.getAttribute('require');

            list = QUI.Controls.getByType(menuRequire);

            var attributes = Object.merge(
                Item.getStorageAttributes(),
                Item.getAttributes()
            );

            if (Item.getAttribute('originalIcon')) {
                attributes.icon = Item.getAttribute('originalIcon');
            }

            return new Promise(function (resolve) {

                if (list.length) {
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
                    return;
                }

                this.$createControl(menuRequire, attributes).then(resolve);

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
