/**
 * @module controls/users/auth/GlobalAuthenticatorSettings
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 */
define('controls/users/auth/GlobalAuthenticatorSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'Mustache',

    'text!controls/users/auth/GlobalAuthenticatorSettings.Row.html'

], function (QUI, QUIControl, QUIAjax, QUILocale, Mustache, templateRow) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({
        Type: 'controls/users/auth/GlobalAuthenticatorSettings',
        Extends: QUIControl,

        Binds: [
            '$onChange'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$rows = [];

            this.addEvents({
                onImport: this.$onImport,
                onRefresh: this.$onRefresh
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this;

            QUIAjax.get('ajax_users_authenticator_globalAuthenticators', function (result) {
                var available = result.available,
                    globals = result.global;

                new Element('div', {
                    html: QUILocale.get(lg, 'quiqqer.settings.auth.global.desc')
                }).inject(self.getElm(), 'after');

                var i, r, len, rlen, NewRow;

                var Row = self.getElm().getParent('tr');
                var rows = [];

                for (i = 0, len = available.length; i < len; i++) {
                    NewRow = new Element('tr', {
                        html: Mustache.render(templateRow, {
                            title: available[i].title,
                            description: available[i].description,
                            authenticator: available[i].authenticator
                        })
                    }).inject(Row, 'after');

                    rows.push(NewRow);

                    NewRow.getElement('input').addEvent('change', self.$onChange);
                }

                for (i = 0, len = globals.length; i < len; i++) {
                    for (r = 0, rlen = rows.length; r < rlen; r++) {
                        rows[r].getElements('[name="' + globals[i].escapeRegExp() + '"]')
                               .set('checked', true);
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
            var checked = this.$rows.filter(function (Row) {
                return Row.getElement('input').checked;
            }).map(function (Row) {
                return Row.getElement('input').name;
            });

            if (this.getElm().nodeName == 'INPUT') {
                this.getElm().value = JSON.encode(checked);
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
                    onError: reject
                });
            }.bind(this));
        }
    });
});
