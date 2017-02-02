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

        initialize: function (options) {
            this.parent(options);

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
                    globals = result.global;

                new Element('div', {
                    html: QUILocale.get(lg, 'quiqqer.settings.auth.global.desc')
                }).inject(self.getElm(), 'after');

                var Row = self.getElm().getParent('tr');

                for (var i = 0, len = available.length; i < len; i++) {
                    new Element('tr', {
                        html: Mustache.render(templateRow, {
                            title: available[i].title,
                            authenticator: available[i].authenticator
                        })
                    }).inject(Row, 'after');
                }
            });
        }
    });
});
