/**
 * Cache type setting
 *
 * @module controls/cache/CacheType
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/cache/General', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUIButton, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/cache/General',

        Binds: [
            '$onImport',
            '$onTypeChange',
            'redisCheck'
        ],

        initialize: function (Settings) {
            this.$Settings   = Settings;
            this.$RedisCheck = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onImport: function () {
            var i, len, Table;

            var Elm        = this.getElm(),
                CacheType  = Elm.querySelector('[name="general.cacheType"]'),
                RedisTable = Elm.querySelector('[name="general.redis"]').getParent('table'),
                data       = this.$Settings.serialize();

            // default setting check
            if (typeof data.config.handlers !== 'undefined') {
                var handlers = data.config.handlers;

                for (i in handlers) {
                    if (!handlers.hasOwnProperty(i)) {
                        continue;
                    }

                    if (parseInt(handlers[i])) {
                        CacheType.value = i;
                    }
                }
            }

            // type changing
            CacheType.addEventListener('change', this.$onTypeChange);

            // table handling
            var tables = Elm.querySelectorAll('table');

            for (i = 0, len = tables.length; i < len; i++) {
                Table = tables[i];

                if (Table.querySelector('[name="general.nocache"]')) {
                    continue;
                }

                Table.setStyle('display', 'none');
            }

            this.$onTypeChange();

            // redis check
            this.$RedisCheck = new QUIButton({
                text  : QUILocale.get('quiqqer/quiqqer', 'quiqqer.settings.cache.redis.check.button'),
                events: {
                    onClick: this.redisCheck
                }
            }).inject(
                RedisTable.getElement('tbody label')
            );
        },

        /**
         * event: on type change
         */
        $onTypeChange: function () {
            var Elm        = this.getElm(),
                CacheType  = Elm.querySelector('[name="general.cacheType"]'),
                RedisTable = Elm.querySelector('[name="general.redis"]').getParent('table'),
                APCTable   = Elm.querySelector('[name="apc.namespace"]').getParent('table'),
                MemTable   = Elm.querySelector('[name="memcache.servers"]').getParent('table'),
                FileTable  = Elm.querySelector('[name="filesystem.path"]').getParent('table');

            RedisTable.setStyle('display', 'none');
            APCTable.setStyle('display', 'none');
            MemTable.setStyle('display', 'none');
            FileTable.setStyle('display', 'none');

            switch (CacheType.value) {
                case 'apc':
                    APCTable.setStyle('display', null);
                    break;

                case 'memcache':
                    MemTable.setStyle('display', null);
                    break;

                case 'redis':
                    RedisTable.setStyle('display', null);
                    break;

                default:
                case 'filesystem':
                    FileTable.setStyle('display', null);
                    break;
            }
        },

        /**
         * Check redis server
         */
        redisCheck: function () {
            var self = this;

            this.$RedisCheck.setAttribute('text', '<span class="fa fa-spinner fa-spin"></span>');

            QUIAjax.get('ajax_system_cache_redisCheck', function (result) {
                self.$RedisCheck.setAttribute(
                    'text',
                    QUILocale.get('quiqqer/quiqqer', 'quiqqer.settings.cache.redis.check.button')
                );

                var message = result.message;
                var status  = result.status;

                QUI.getMessageHandler().then(function (MH) {
                    console.log(status);
                    console.log(typeof status);

                    if (status === -1) {
                        MH.addError(message);
                        return;
                    }

                    if (status) {
                        MH.addSuccess(message);
                        return;
                    }

                    MH.addError(message);
                });
            }, {
                server: this.getElm().querySelector('[name="general.redis"]').value
            });
        }
    });
});
