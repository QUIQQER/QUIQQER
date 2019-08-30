/**
 * Cache type setting
 *
 * @module controls/cache/CacheType
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/cache/General', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/cache/General',

        Binds: [
            '$onImport',
            '$onTypeChange'
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
            var i, len, Table;

            var Elm       = this.getElm(),
                CacheType = Elm.querySelector('[name="general.cacheType"]'),
                data      = this.$Settings.serialize();

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
        }
    });
});
