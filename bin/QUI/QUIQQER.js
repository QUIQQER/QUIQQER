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
         * Get license data of QUIQQER system
         *
         * @return {Promise}
         */
        getLicenseData: function() {
            return new Promise(function(resolve, reject) {
                QUIAjax.get('ajax_licenseKey_getAuthData', resolve, {
                    onError: reject
                })
            });
        }
    };
});
