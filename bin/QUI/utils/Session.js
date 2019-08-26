/**
 * Locker
 *
 * @module utils/Lock
 * @author www.pcsg.de (Henning Leutz)
 */
define('utils/Session', ['Ajax'], function (QUIAjax) {
    "use strict";

    return {
        /**
         * Set a value to the session
         *
         * @param {String} key
         * @param {String|Array|Object|Number} value
         * @returns {Promise}
         */
        set: function (key, value) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_session_set', resolve, {
                    key    : key,
                    value  : JSON.encode(value),
                    onError: reject
                });
            });
        },

        /**
         * Remove a value from the session
         *
         * @param {String} key
         * @returns {Promise}
         */
        remove: function (key) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_session_remove', resolve, {
                    key    : key,
                    onError: reject
                });
            });
        },

        /**
         * Is an element locked?
         *
         * @param {String} key
         * @returns {Promise}
         */
        get: function (key) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_session_get', function (value) {
                    try {
                        resolve(JSON.decode(value));
                    } catch (e) {
                        resolve(false);
                    }
                }, {
                    key    : key,
                    onError: reject
                });
            });
        }
    };
});