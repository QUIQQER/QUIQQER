/**
 * Package Manager / System Update
 *
 * @module controls/packages/Panel
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/packages/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'Locale',

    'css!controls/packages/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUILocale) {
    "use strict";

    var lg = 'quiqqer/core';

    /**
     * @class controls/packages/Panel
     *
     * @param {Object} options - QDOM panel params
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/packages/Panel',

        Binds: [
            'toggleMenu',
            'loadInstalled',
            'loadSearch',
            'loadServer',
            'loadSystem',
            'loadSystemCheck',
            'checkUpdates',
            'executeCompleteSetup',
            '$onCreate',
            '$onShow',
            '$loadControl',
            'loadLicense'
        ],

        initialize: function (options) {
            this.parent(options);

            // defaults
            this.setAttribute('title', QUILocale.get(lg, 'packages.panel.title'));
            this.setAttribute('icon', URL_BIN_DIR + '16x16/quiqqer.png');

            this.$Control = null;
            this.Loader.setAttribute('closetime', 600000); // closing time 10minutes

            this.addEvents({
                onCreate: this.$onCreate,
                onShow  : this.$onShow
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self = this;

            this.addButton({
                name  : 'menu',
                title : QUILocale.get('quiqqer/core', 'packages.panel.menu'),
                icon  : 'fa fa-bars',
                events: {
                    onClick: this.toggleMenu
                },
                styles: {
                    width: 60
                }
            });


            // left categories
            this.addCategory({
                name  : 'system',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.update'),
                image : 'fa fa-check-circle-o',
                events: {
                    onActive: this.loadSystem
                }
            });

            this.addCategory({
                name  : 'search',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.search'),
                image : 'fa fa-plug',
                events: {
                    onClick : function () {
                        self.$Before = self.getActiveCategory();
                    },
                    onActive: this.loadSearch
                }
            });

            this.addCategory({
                name  : 'installed',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.installed'),
                image : 'fa fa-puzzle-piece',
                events: {
                    onActive: this.loadInstalled
                }
            });

            this.addCategory({
                name  : 'server',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.server'),
                image : 'fa fa-server',
                events: {
                    onActive: this.loadServer
                }
            });

            this.addCategory({
                name  : 'systemcheck',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.systemCheck.cat.title'),
                image : 'fa fa-info-circle',
                events: {
                    onActive: this.loadSystemCheck
                }
            });

            this.addCategory({
                name  : 'phpinfo',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.settings'),
                image : 'fa fa-gears',
                events: {
                    onClick: function () {
                        require(['Menu'], function (Menu) {
                            var Item = Menu.getChildren()
                                .getChildren('settings')
                                .getChildren('quiqqer')
                                .getChildren('/settings/quiqqer/core/');

                            Menu.menuClick(Item);
                        });
                    }
                }
            });

            this.addCategory({
                name  : 'license',
                text  : QUILocale.get('quiqqer/core', 'packages.panel.category.license'),
                image : 'fa fa-key',
                events: {
                    onActive: this.loadLicense
                }
            });

            this.$Categories.addClass('packages-panel-categories');

            this.getContent().setStyles({
                padding : 0,
                position: 'relative'
            });
        },

        /**
         * Toggle the menu
         */
        toggleMenu: function () {
            if (this.$Categories.hasClass('qui-panel-categories-minimize')) {
                this.maximizeCategory();
                this.getButtons('menu').setAttribute('icon', 'fa fa-bars');
            } else {
                this.minimizeCategory();
                this.getButtons('menu').setAttribute('icon', 'fa fa-ellipsis-v');
            }
        },

        /**
         * event: on open
         */
        $onShow: function () {
            this.getCategory('system').click();
        },

        /**
         * Load the installed packages
         */
        loadInstalled: function () {
            this.$loadControl('controls/packages/Installed');
        },

        /**
         * Load the system information
         */
        loadSystem: function () {
            this.$loadControl('controls/packages/System');
        },

        /**
         * Load the server list
         */
        loadServer: function () {
            this.$loadControl('controls/packages/Server');
        },

        /**
         * Load the system check
         */
        loadSystemCheck: function () {
            this.$loadControl('controls/packages/SystemCheck');
        },

        /**
         * Load QUIQQER license info
         */
        loadLicense: function () {
            this.$loadControl('controls/licenseKey/LicenseKey');
        },

        /**
         * Load the package search
         */
        loadSearch: function () {
            var self = this;

            var Sheet = this.createSheet({
                buttons: false,
                icon   : 'fa fa-plug',
                title  : QUILocale.get('quiqqer/core', 'packages.panel.category.search')
            });

            Sheet.addEvent('show', function () {
                self.Loader.show();

                require(['controls/packages/Search'], function (Search) {
                    new Search({
                        events: {
                            onLoad       : function () {
                                self.Loader.hide();
                            },
                            onSearchBegin: function () {
                                self.Loader.show();
                            },
                            onSearchEnd  : function () {
                                self.Loader.hide();
                            }
                        }
                    }).inject(Sheet.getContent());
                });
            });

            Sheet.addEvent('close', function () {
                if (self.$Before) {
                    self.$Before.click();
                }
            });

            Sheet.addButton({
                text: 'test'
            });

            Sheet.show();
        },

        /**
         * internal control category loading
         *
         * @param {String} ctrl
         * @return {Promise}
         */
        $loadControl: function (ctrl) {
            var self = this;

            this.Loader.show();

            return this.$hideControl(self.$Control).then(function () {
                return new Promise(function (resolve) {
                    require([ctrl], function (Control) {
                        self.$Control = new Control({
                            events: {
                                onLoad      : function () {
                                    self.Loader.hide();
                                    resolve();
                                },
                                onShowLoader: function () {
                                    self.Loader.show();
                                },
                                onHideLoader: function () {
                                    self.Loader.hide();
                                }
                            }
                        });

                        self.$Control.getElm().setStyles({
                            opacity : 0,
                            position: 'relative',
                            top     : -50
                        });

                        self.$Control.inject(self.getContent());

                        // refresh buttons
                        self.getButtons().each(function (Button) {
                            switch (Button.getAttribute('name')) {
                                case 'menuSeparator':
                                case 'menu':
                                    return;
                            }

                            Button.destroy();
                        });

                        // buttons
                        if ("getButtons" in self.$Control) {
                            // refresh buttons
                            self.addButton({
                                type: 'separator'
                            });

                            self.$Control.getButtons().each(function (btn) {
                                self.addButton(btn);
                            });
                        }
                    });
                }).then(function () {
                    return self.$showControl(self.$Control);
                }).then(function () {
                    return self.$Control;
                });
            });

        },

        /**
         * Hide the control
         *
         * @param {Object} [Control] - QUIControl
         * @returns {Promise}
         */
        $hideControl: function (Control) {
            return new Promise(function (resolve) {
                if (typeof Control === 'undefined' || !Control) {
                    return resolve();
                }

                moofx(Control.getElm()).animate({
                    opacity: 0,
                    top    : -50
                }, {
                    duration: 250,
                    callback: function () {
                        Control.destroy();
                        resolve();
                    }
                });
            });
        },

        /**
         * Show the control
         *
         * @param {Object} [Control] - QUIControl
         * @returns {Promise}
         */
        $showControl: function (Control) {
            return new Promise(function (resolve) {
                if (typeof Control === 'undefined' || !Control) {
                    return resolve();
                }

                var Elm = Control.getElm();

                Elm.setStyles({
                    opacity: 0,
                    top    : -50
                });

                moofx(Elm).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 250,
                    callback: resolve
                });
            });
        }
    });
});
