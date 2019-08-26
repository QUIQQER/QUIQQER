/**
 * @module controls/users/auth/GlobalAuthenticatorSettings
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/users/auth/GlobalAuthenticatorSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'Mustache',

    'text!controls/users/auth/GlobalAuthenticatorSettings.Row.html',
    'text!controls/users/auth/GlobalAuthenticatorSettings.Header.html'

], function (QUI, QUIControl, QUIAjax, QUILocale, Mustache, templateRow, templateHeader) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({
        Type   : 'controls/users/auth/GlobalAuthenticatorSettings',
        Extends: QUIControl,

        Binds: [
            '$onChange'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$rows = [];

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this;

            QUIAjax.get('ajax_users_authenticator_globalAuthenticators', function (result) {
                var available = result.available,
                    globals   = result.global;

                new Element('div', {
                    html: QUILocale.get(lg, 'quiqqer.settings.auth.global.desc')
                }).inject(self.getElm(), 'after');

                var i, r, len, rLen, NewRow;

                var Row  = self.getElm().getParent('tr');
                var rows = [];

                for (i = 0, len = available.length; i < len; i++) {
                    NewRow = new Element('tr', {
                        html: Mustache.render(templateRow, {
                            title        : available[i].title,
                            description  : available[i].description,
                            authenticator: available[i].authenticator
                        })
                    }).inject(Row, 'after');

                    rows.push(NewRow);

                    NewRow.getElements('input').addEvent('change', self.$onChange);
                }

                new Element('tr', {
                    html: Mustache.render(templateHeader, {})
                }).inject(Row, 'after');

                var condition;
                var frontend = globals.frontend;
                var backend  = globals.backend;

                for (i = 0, len = frontend.length; i < len; i++) {
                    for (r = 0, rLen = rows.length; r < rLen; r++) {
                        condition = [];
                        condition.push('[name="' + frontend[i].escapeRegExp() + '"]');
                        condition.push('[value="frontend"]');
                        condition = condition.join('');

                        rows[r].getElements(condition).set('checked', true);
                    }
                }

                for (i = 0, len = backend.length; i < len; i++) {
                    for (r = 0, rLen = rows.length; r < rLen; r++) {
                        condition = [];
                        condition.push('[name="' + backend[i].escapeRegExp() + '"]');
                        condition.push('[value="backend"]');
                        condition = condition.join('');

                        rows[r].getElements(condition).set('checked', true);
                    }
                }

                self.$rows = rows;
                self.$onChange();
            });
        },

        /**
         * event: on checkbox change
         */
        $onChange: function () {
            var i, len, elm;
            var inputs = this.$rows.map(function (Row) {
                var nodes = Row.getElements('input');
                return [nodes[0], nodes[1]];
            });

            inputs = inputs.flat();

            var result = {};

            for (i = 0, len = inputs.length; i < len; i++) {
                elm = inputs[i];

                if (typeof result[elm.name] === 'undefined') {
                    result[elm.name] = {};
                }

                result[elm.name][elm.value] = elm.checked;
            }

            if (this.getElm().nodeName === 'INPUT') {
                this.getElm().value = JSON.encode(result);
            }
        },

        /**
         * Save the authenticator settings
         *
         * @returns {Promise}
         */
        save: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_users_authenticator_save', resolve, {
                    authenticators: this.getElm().value,
                    onError       : reject
                });
            }.bind(this));
        }
    });
});
