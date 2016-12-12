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
         * @returns {Promise}
         */
        lock: function (key) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_lock_lock', resolve, {
                    'package': 'quiqqer/quiqqer',
                    key      : key,
                    onError  : reject
                });
            });
        },

        /**
         * Unlock an element
         *
         * @param {String} key
         * @returns {Promise}
         */
        unlock: function (key) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_lock_unlock', resolve, {
                    'package': 'quiqqer/quiqqer',
                    key      : key,
                    onError  : reject
                });
            });
        },

        /**
         * Is an element locked?
         *
         * @param {String} key
         * @returns {Promise}
         */
        isLocked: function (key) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_lock_isLocked', resolve, {
                    'package': 'quiqqer/quiqqer',
                    key      : key,
                    onError  : reject
                });
            });
        },

        /**
         * Return the last locktime from an element
         *
         * @param {String} key
         * @returns {Promise}
         */
        getLockTime: function (key) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_lock_getLocktime', resolve, {
                    'package': 'quiqqer/quiqqer',
                    key      : key,
                    onError  : reject
                });
            });
        }
    };
});