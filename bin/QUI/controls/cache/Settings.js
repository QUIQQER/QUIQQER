/**
 * Cache Settings
 *
 * @module controls/cache/Settings
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/cache/Settings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale',

    'css!controls/cache/Settings.css'

], function (QUI, QUIControl, QUIButton, Ajax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/cache/Settings',

        Binds: [
            '$onImport'
        ],

        initialize: function (Settings) {
            this.$Settings = Settings;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onImport: function () {
            var self = this;

            var tables           = this.getElm().getElements('table'),
                ClearCacheBody   = new Element('div.quiqqer-settings-cache-container').inject(
                    tables[0].getElement('tbody')
                ),
                QuiqqerCacheBody = new Element('div.quiqqer-settings-cache-container').inject(
                    tables[2].getElement('tbody')
                ),
                PurgeCacheBody   = new Element('div.quiqqer-settings-cache-container').inject(
                    tables[1].getElement('tbody')
                );

            tables.addClass('quiqqer-settings-cache-table');
            ClearCacheBody.addClass('quiqqer-settings-cache-tbody');
            QuiqqerCacheBody.addClass('quiqqer-settings-cache-tbody');
            PurgeCacheBody.addClass('quiqqer-settings-cache-tbody');

            // complete
            new QUIButton({
                text     : QUILocale.get(lg, 'quiqqer.settings.cache.clear.complete'),
                textimage: 'fa fa-trash-o',
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        self.clear(
                            {complete: true},
                            function () {
                                Btn.setAttribute('textimage', 'fa fa-trash-o');
                            }
                        );
                    }
                }
            }).inject(ClearCacheBody);


            // QUIQQER
            new QUIButton({
                text     : QUILocale.get(lg, 'quiqqer.settings.cache.clear.quiqqer'),
                textimage: URL_BIN_DIR + '16x16/quiqqer.png',
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        self.clear(
                            {quiqqer: true},
                            function () {
                                Btn.setAttribute('textimage', URL_BIN_DIR + '16x16/quiqqer.png');
                            }
                        );
                    }
                }
            }).inject(QuiqqerCacheBody);

            var quiqqerButtons = [{
                name: 'quiqqer-projects',
                icon: 'fa fa-home'
            }, {
                name: 'quiqqer-groups',
                icon: 'fa fa-group'
            }, {
                name: 'quiqqer-users',
                icon: 'fa fa-user'
            }, {
                name: 'quiqqer-permissions',
                icon: 'fa fa-shield'
            }, {
                name: 'quiqqer-packages',
                icon: 'fa fa-puzzle-piece'
            }];

            var btnClick = function (Btn) {
                Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                var icon    = Btn.getAttribute('data').icon;
                var name    = Btn.getAttribute('data').name;
                var options = {};

                options[name] = true;

                self.clear(options, function () {
                    Btn.setAttribute('textimage', icon);
                });
            };

            for (var i = 0, len = quiqqerButtons.length; i < len; i++) {
                new QUIButton({
                    text     : QUILocale.get(lg, 'quiqqer.settings.cache.clear.quiqqer-' + quiqqerButtons[i].name),
                    textimage: quiqqerButtons[i].icon,
                    data     : quiqqerButtons[i],
                    events   : {
                        onClick: btnClick
                    }
                }).inject(QuiqqerCacheBody);
            }


            // purge
            new QUIButton({
                text     : QUILocale.get(lg, 'quiqqer.settings.cache.purge.button'),
                textimage: 'fa fa-paint-brush',
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        self.purge(function () {
                            Btn.setAttribute('textimage', 'fa fa-paint-brush');
                        });
                    }
                }
            }).inject(PurgeCacheBody);
        },


        /**
         * Clear the cache
         *
         * @param {Object} [params] - Caches to clear as object attribute: plugins, compile, template
         * @param {boolean} [params.plugins] - Clear plugins cache
         * @param {boolean} [params.compile] - Clear system cache
         * @param {boolean} [params.templates] - Clear templates cache
         * @param {boolean} [params.complete] - Clears everything in the cache
         * @param {Function} [callback] - (optional), callback function
         */
        clear: function (params, callback) {
            Ajax.get('ajax_system_cache_clear', function () {
                if (typeof callback !== 'undefined') {
                    callback();
                }

                QUI.getMessageHandler(function (QUI) {
                    QUI.addSuccess(
                        QUILocale.get(lg, 'message.clear.cache.successful')
                    );
                });
            }, {
                params: JSON.encode(params)
            });
        },

        /**
         * Purge the cache
         *
         * @param {Function} [callback] - (optional), callback function
         */
        purge: function (callback) {
            Ajax.get('ajax_system_cache_purge', function () {
                if (typeof callback !== 'undefined') {
                    callback();
                }

                QUI.getMessageHandler(function (QUI) {
                    QUI.addSuccess(
                        QUILocale.get(lg, 'message.clear.cache.successful')
                    );
                });
            });
        }
    });

});
