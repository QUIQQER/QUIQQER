/**
 * QUIQQER
 *
 * @author www.pcsg.de (Henning Leutz)
 */
define('QUIQQER', ['Ajax', 'Packages'], function (QUIAjax, Packages) {
    "use strict";

    var availableLanguages = [];

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
                QUIAjax.get('ajax_isAuth', function (User) {
                    if (!User.id) {
                        resolve(false);
                        return;
                    }

                    window.QUIQQER_USER = User;
                    resolve(true);
                }, {
                    onError: reject
                });
            });
        },

        /**
         * Return the available languages
         *
         * @return {Promise}
         */
        getAvailableLanguages: function () {
            if (availableLanguages.length) {
                return Promise.resolve(availableLanguages);
            }

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_system_getAvailableLanguages', function (languages) {
                    availableLanguages = languages;
                    resolve(languages);
                });
            });
        }
    };
});
