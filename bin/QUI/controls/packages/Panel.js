/**
 * Package Manager / System Update
 *
 * @module controls/packages/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require Locale
 * @require Ajax
 * @require classes/packages/Manager
 * @require css!controls/packages/Panel.css
 */
define('controls/packages/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'Locale',

    'css!controls/packages/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUILocale) {
    "use strict";

    var lg = 'quiqqer/system';

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
            'loadPHPInfo',
            'viewTile',
            'viewList',
            'checkUpdates',
            'executeCompleteSetup',
            '$onCreate',
            '$onShow'
        ],

        initialize: function (options) {
            this.parent(options);

            // defaults
            this.setAttribute('title', QUILocale.get(lg, 'packages.panel.title'));
            this.setAttribute('icon', URL_BIN_DIR + '16x16/quiqqer.png');

            this.$Control = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onShow  : this.$onShow
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            this.addButton({
                name  : 'menu',
                title : QUILocale.get('quiqqer/quiqqer', 'packages.panel.menu'),
                icon  : 'fa fa-bars',
                events: {
                    onClick: this.toggleMenu
                },
                styles: {
                    width: 60
                }
            });

            this.addButton({
                type: 'seperator',
                name: 'menuSeperator'
            });

            this.addButton({
                name  : 'viewTile',
                title : QUILocale.get(lg, 'packages.panel.menu'),
                icon  : 'fa fa-th',
                events: {
                    onClick: this.viewTile
                }
            });

            this.addButton({
                name  : 'viewList',
                title : QUILocale.get(lg, 'packages.panel.menu'),
                icon  : 'fa fa-th-list',
                events: {
                    onClick: this.viewList
                }
            });

            // left categories
            this.addCategory({
                name  : 'search',
                text  : QUILocale.get('quiqqer/quiqqer', 'packages.panel.category.search'),
                image : 'fa fa-search',
                events: {
                    onActive: this.loadSearch
                }
            });

            this.addCategory({
                name  : 'installed',
                text  : QUILocale.get('quiqqer/quiqqer', 'packages.panel.category.installed'),
                image : 'fa fa-gift',
                events: {
                    onActive: this.loadInstalled
                }
            });

            this.addCategory({
                name  : 'system',
                text  : QUILocale.get('quiqqer/quiqqer', 'packages.panel.category.update'),
                image : 'fa fa-check-circle-o',
                events: {
                    onActive: this.loadSystem
                }
            });

            this.addCategory({
                name  : 'server',
                text  : QUILocale.get('quiqqer/quiqqer', 'packages.panel.category.server'),
                image : 'fa fa-server',
                events: {
                    onActive: this.loadServer
                }
            });

            this.addCategory({
                name  : 'phpinfo',
                text  : QUILocale.get('quiqqer/quiqqer', 'packages.panel.category.phpinfo'),
                image : 'fa fa-info-circle',
                events: {
                    onActive: this.loadPHPInfo
                }
            });


            this.$Categories.addClass('packages-panel-categories');

            this.getContent().setStyles({
                padding: 0
            });
        },

        toggleMenu: function () {

        },

        /**
         * View tile
         */
        viewTile: function () {
            if ("getList" in this.$Control) {
                this.$Control.getList().viewTile();
            }

            this.getButtons('viewTile').setActive();
            this.getButtons('viewList').setNormal();
        },

        /**
         * View list
         */
        viewList: function () {
            if ("getList" in this.$Control) {
                this.$Control.getList().viewList();
            }

            this.getButtons('viewTile').setNormal();
            this.getButtons('viewList').setActive();
        },

        /**
         * event: on open
         */
        $onShow: function () {
            this.getButtons('viewTile').setActive();
            this.getCategory('search').click();
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
         * Load the server list
         */
        loadPHPInfo: function () {
            this.$loadControl('controls/packages/PHPInfo');
        },

        /**
         * Load the package search
         */
        loadSearch: function () {
            var self = this;

            this.$loadControl('controls/packages/Search').then(function (Search) {
                Search.addEvents({
                    onSearchBegin: function () {
                        self.Loader.show();
                    },
                    onSearchEnd  : function () {
                        self.Loader.hide();
                    }
                });
            });
        },

        /**
         * internal control category loading
         *
         * @param {String} ctrl
         * @return {Promise}
         */
        $loadControl: function (ctrl) {
            var self = this,
                view = 'tile';

            if (this.getButtons('viewList').isActive()) {
                view = 'list';
            }

            this.Loader.show();

            if (self.$Control) {
                self.$Control.destroy();
            }

            return new Promise(function (resolve) {
                require([ctrl], function (Control) {
                    self.$Control = new Control({
                        view  : view,
                        events: {
                            onLoad      : function () {
                                self.Loader.hide();
                            },
                            onShowLoader: function () {
                                self.Loader.show();
                            },
                            onHideLoader: function () {
                                self.Loader.hide();
                            }
                        }
                    }).inject(self.getContent());

                    // refresh buttons
                    self.getButtons().each(function (Button) {
                        switch (Button.getAttribute('name')) {
                            case 'viewTile':
                            case 'viewList':
                            case 'menuSeperator':
                            case 'menu':
                                return;
                        }

                        Button.destroy();
                    });


                    // buttons
                    if ("getButtons" in self.$Control) {
                        // refresh buttons
                        self.addButton({
                            type: 'seperator'
                        });

                        self.$Control.getButtons().each(function (btn) {
                            self.addButton(btn);
                        });
                    }

                    resolve(self.$Control);
                }.bind(this));
            });
        }
    });
});