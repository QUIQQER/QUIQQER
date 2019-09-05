/**
 * Package manager
 *
 * @module classes/packages/StoreApi
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 * @require Locale
 */
define('classes/packages/StoreApi', [

    'Packages',
    'Ajax'

], function (Packages, QUIAjax) {
    "use strict";

    return new Class({

        Type: 'classes/packages/StoreApi',

        /**
         * Get all installed packages
         *
         * @return {Promise}
         */
        getInstalledPackages: function () {
            return Packages.getInstalledPackages();
        },

        /**
         * Install a package
         *
         * @param {String} pkg - package name
         * @param {String} version - package version
         */
        installPackage: function (pkg, version) {
            // If a non-dev version is installed, always install latest version of the chosen
            // major version.
            if (version.indexOf("dev-") === -1) {
                version = version.split('.')[0] + '.*';
            }

            return Packages.installPackage(pkg, version);
        },

        /**
         * Get license data used for authentication in the Package Store
         *
         * @return {Promise}
         */
        getLicenseAuthData: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_licenseKey_getAuthData', resolve, {
                    onError: reject
                });
            });
        },

        /**
         * Get PHP max_execution_time setting
         *
         * @return {Promise}
         */
        getMaxExecutionTime: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_packagestore_getMaxExecutionTime', resolve, {
                    onError: reject
                });
            });
        },

        /**
         * Get QUIQQER Version of current system
         *
         * @return {Promise}
         */
        getQuiqqerVersion: function () {
            if (typeof QUIQQER_VERSION === 'undefined') {
                return Promise.resolve(false);
            }

            return Promise.resolve(QUIQQER_VERSION);
        }
    });
});
