/**
 * QUIQQER
 *
 * @author www.pcsg.de (Henning Leutz)
 */
define('QUIQQER', ['Ajax', 'Packages'], function (QUIAjax, Packages) {
    "use strict";

    return {
        /**
         * Return the current QUIQQER Version
         *
         * @returns {Promise}
         */
        version: function () {
            return new Promise(function (resolve) {
                QUIAjax.get('ajax_system_version', resolve);
            });
        },

        /**
         * Return all information of QUIQQER
         *
         * @returns {Promise}
         */
        getInformation: function () {
            return Packages.getPackageLock('quiqqer/quiqqer');
        },

        /**
         * checks the authentication status
         * can be used to check the message handler, too
         *
         * @return {Promise}
         */
        isAuthenticated: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_isAuth', resolve, {
                    onError: reject
                });
            });
        }
    };
});
