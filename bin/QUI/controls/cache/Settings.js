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
    'Locale'

], function (QUI, QUIControl, QUIButton, Ajax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/system';

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

            var ClearCompleteCacheButton = new QUIButton({
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
            }).replaces(this.$Elm.getElement('[name="clearCompleteCache"]'));

            var ClearSystemCacheButton = new QUIButton({
                text     : QUILocale.get(lg, 'quiqqer.settings.cache.clear.compile'),
                textimage: 'fa fa-server',
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        self.clear(
                            {compile: true},
                            function () {
                                Btn.setAttribute('textimage', 'fa fa-server');
                            }
                        );
                    }
                }
            }).replaces(this.$Elm.getElement('[name="clearSystemCache"]'));

            var ClearPluginCacheButton = new QUIButton({
                text     : QUILocale.get(lg, 'quiqqer.settings.cache.clear.plugins'),
                textimage: 'fa fa-gift',
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        self.clear(
                            {plugins: true},
                            function () {
                                Btn.setAttribute('textimage', 'fa fa-gift');
                            }
                        );
                    }
                }
            }).replaces(this.$Elm.getElement('[name="clearPluginCache"]'));

            var ClearTemplateCacheButton = new QUIButton({
                text     : QUILocale.get(lg, 'quiqqer.settings.cache.clear.templates'),
                textimage: 'fa fa-file-text',
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        self.clear(
                            {templates: true},
                            function () {
                                Btn.setAttribute('textimage', 'fa fa-file-text');
                            }
                        );
                    }
                }
            }).replaces(this.$Elm.getElement('[name="clearTemplateCache"]'));


            var PurgeCacheButton = new QUIButton({
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
            }).replaces(this.$Elm.getElement('[name="purgeCache"]'));

            ClearCompleteCacheButton.getElm().addClass('field-container-field');
            ClearSystemCacheButton.getElm().addClass('field-container-field');
            ClearPluginCacheButton.getElm().addClass('field-container-field');
            ClearTemplateCacheButton.getElm().addClass('field-container-field');

            PurgeCacheButton.getElm().addClass('field-container-field');
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
