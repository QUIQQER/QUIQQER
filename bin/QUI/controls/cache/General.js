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
            var CacheType = document.getElement('[name="general.cacheType"]');
            var data      = this.$Settings.serialize();

            if (typeof data.config.handlers !== 'undefined') {
                var handlers = data.config.handlers;

                for (var i in handlers) {
                    if (!handlers.hasOwnProperty(i)) {
                        continue;
                    }

                    if (parseInt(handlers[i])) {
                        CacheType.value = i;
                    }
                }
            }
        }
    });
});
