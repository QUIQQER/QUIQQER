/**
 * Cache type setting for long time cache
 *
 * @module controls/cache/LongTime
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/cache/LongTime', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUIButton, QUIConfirm, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/cache/LongTime',

        Binds: [
            '$onImport',
            '$onTypeChange',
            'redisCheck',
            'killLongTimeCache'
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
            var Elm        = this.getElm(),
                CacheType  = Elm.querySelector('[name="longtime.type"]'),
                RedisTable = Elm.querySelector('[name="longtime.redis_server"]').getParent('table');


            // table handling
            var tables = Elm.querySelectorAll('table');

            for (i = 0, len = tables.length; i < len; i++) {
                Table = tables[i];

                if (Table.querySelector('[name="longtime.type"]')) {
                    continue;
                }

                Table.setStyle('display', 'none');
            }

            new Element('button', {
                type  : 'buttons',
                class : 'qui-button',
                html  : 'Kompletten Langzeitcache leeren',
                styles: {
                    'float'     : 'right',
                    marginBottom: 20
                },
                events: {
                    click: this.killLongTimeCache
                }
            }).inject(this.getElm().getElement('table'), 'after');

            // type changing
            CacheType.addEventListener('change', this.$onTypeChange);
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
                CacheType  = Elm.querySelector('[name="longtime.type"]'),
                RedisTable = Elm.querySelector('[name="longtime.redis_server"]').getParent('table'),
                FileTable  = Elm.querySelector('[name="longtime.file_path"]').getParent('table'),
                MongoTable = Elm.querySelector('[name="longtime.mongo_collection"]').getParent('table');

            RedisTable.setStyle('display', 'none');
            FileTable.setStyle('display', 'none');
            MongoTable.setStyle('display', 'none');

            switch (CacheType.value) {
                case 'mongo':
                    MongoTable.setStyle('display', null);
                    break;

                case 'redis':
                    RedisTable.setStyle('display', null);
                    break;

                default:
                case 'file':
                    FileTable.setStyle('display', null);
                    break;
            }
        },

        /**
         * redis check
         **/
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
                server: this.getElm().querySelector('[name="longtime.redis_server"]').value
            });
        },

        /**
         * Opens the kill time cache window
         */
        killLongTimeCache: function () {
            new QUIConfirm({
                maxHeight  : 300,
                maxWidth   : 600,
                icon       : 'fa fa-ban',
                texticon   : 'fa fa-ban',
                title      : QUILocale.get(lg, 'window.long.term.cache.clear.title'),
                information: QUILocale.get(lg, 'window.long.term.cache.clear.information'),
                text       : QUILocale.get(lg, 'window.long.term.cache.clear.text'),
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        QUIAjax.post('ajax_system_cache_clear', function () {
                            Win.Loader.hide();
                            Win.close();

                            QUI.getMessageHandler().then(function (MH) {
                                MH.addSuccess(
                                    QUILocale.get(lg, 'message.long.term.cache.clear.success')
                                );
                            });
                        }, {
                            longterm: 1
                        });
                    }
                }
            }).open();
        }
    });
});
