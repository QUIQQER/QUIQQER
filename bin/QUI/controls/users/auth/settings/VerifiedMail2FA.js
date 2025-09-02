/**
 * QUIQQER Authentication Settings via email code
 */
define('controls/users/auth/settings/VerifiedMail2FA', [
    
    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, QUILocale, QUIAjax) {
    'use strict';

    return new Class({

        Extends: QUIControl,
        Type: 'controls/users/auth/settings/VerifiedMail2FA',

        Binds: [
            '$onImport'
        ],

        /**
         * construct
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.Loader = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {

        }
    });
});
