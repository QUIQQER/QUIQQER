/**
 * Locker
 *
 * @module utils/Lock
 * @author www.pcsg.de (Henning Leutz)
 */
define('utils/Lock', ['Ajax'], function (QUIAjax) {
    "use strict";

    return {
        /**
         * Lock an element
         *
         * @param {String} key
         * @param {String} pkg
         * @returns {Promise}
         */
        lock: function (key, pkg) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_lock_lock', resolve, {
                    'package': pkg,
                    key      : key,
                    onError  : reject
                });
            });
        },

        /**
         * Unlock an element
         *
         * @param {String} key
         * @param {String} pkg
         * @returns {Promise}
         */
        unlock: function (key, pkg) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_lock_unlock', resolve, {
                    'package': pkg,
                    key      : key,
                    onError  : reject
                });
            });
        },

        /**
         * Is an element locked?
         *
         * @param {String} key
         * @param {String} pkg
         * @returns {Promise}
         */
        isLocked: function (key, pkg) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_lock_isLocked', resolve, {
                    'package': pkg,
                    key      : key,
                    onError  : reject
                });
            });
        },

        /**
         * Return the last locktime from an element
         *
         * @param {String} key
         * @param {String} pkg
         * @returns {Promise}
         */
        getLockTime: function (key, pkg) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_lock_getLocktime', resolve, {
                    'package': pkg,
                    key      : key,
                    onError  : reject
                });
            });
        }
    };
});