/**
 * A QUIQQER User
 *
 * @module classes/users/User
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onRefresh [ {classes/users/User} ]
 */
define('classes/users/User', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale'

], function (QUI, DOM, Ajax, Locale) {
    'use strict';

    /**
     * A QUIQQER User
     *
     * @class classes/users/User
     * @param {Number} uid - the user id
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: DOM,
        Type: 'classes/users/User',

        attributes: {}, // user attributes

        initialize: function (uid) {
            this.$uid = uid;
            this.$extras = {};
            this.$loaded = false;

            this.$addresses = false;
        },

        /**
         * Get user id
         *
         * @method classes/users/User#getId
         * @return {Number|String} User-ID
         */
        getId: function () {
            return this.$uid;
        },

        /**
         * Return the username
         *
         * return firstname and lastname if exists
         * return getUsername()
         *
         * @method classes/users/User#getName
         * @return {String} Username
         */
        getName: function () {
            const firstname = this.getAttribute('firstname');
            const lastname = this.getAttribute('lastname');

            if (firstname && lastname) {
                return firstname + ' ' + lastname;
            }

            if (this.getAttribute('displayName') && this.getAttribute('displayName') !== '') {
                return this.getAttribute('displayName');
            }

            return this.getUsername();
        },

        /**
         * Return username
         *
         * @return bool|String
         */
        getUsername: function () {
            return this.getAttribute('username');
        },

        /**
         * Load the user attributes from the db
         *
         * @method classes/users/User#load
         * @param {Function} [onfinish] - (optional), callback
         */
        load: function (onfinish) {
            return new Promise((resolve, reject) => {
                Ajax.get('ajax_users_get', (result) => {
                    this.$loaded = true;
                    this.$addresses = false;

                    let uid = 0;

                    if ('id' in result && result.id > 10) {
                        uid = result.id;
                    }

                    // user not found
                    if (!uid) {
                        this.$uid = 0;

                        this.setAttributes({
                            username: 'not found'
                        });

                        if (typeof onfinish === 'function') {
                            onfinish(this);
                        }

                        this.fireEvent('refresh', [this]);

                        require(['Users'], (Users) => {
                            Users.onRefreshUser(this);
                            reject(this);
                        });

                        return;
                    }


                    if (result.extras) {
                        this.$extras = result.extras;
                        delete result.extras;
                    }

                    this.setAttributes(result);

                    if (typeof onfinish === 'function') {
                        onfinish(this);
                    }

                    this.fireEvent('refresh', [this]);

                    require(['Users'], (Users) => {
                        Users.onRefreshUser(this);
                        resolve(this);
                    });

                }, {
                    uid: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Loads the user attributes from the db if not yet loaded
         *
         * @return {Promise}
         */
        loadIfNotLoaded: function () {
            if (this.$loaded) {
                return Promise.resolve(this);
            }

            return this.load();
        },

        /**
         * the user has been loaded once?
         *
         * @return {Boolean}
         */
        isLoaded: function () {
            return this.$loaded;
        },

        /**
         * Save the user attributes to the database
         *
         * @method classes/users/User#save
         * @param {Object} [params]     - (optional), extra ajax params
         * @param {Function} [callback] - (optional),
         * @return {Promise}
         */
        save: function (params, callback) {
            return new Promise((resolve, reject) => {
                if (!this.$uid) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    resolve();
                    return;
                }

                this.$addresses = false;

                require(['Users'], (Users) => {
                    Users.saveUser(this, params).then(() => {
                        if (typeof callback === 'function') {
                            callback();
                        }

                        resolve();
                    }).catch(reject);
                });
            });
        },

        /**
         * Activate the user
         *
         * @method classes/users/User#activate
         * @param {Function} [onfinish] - (optional), callback function, calls if activation is finish
         * @return {Promise}
         */
        activate: function (onfinish) {
            return new Promise((resolve, reject) => {
                if (!this.$uid) {
                    if (typeof onfinish === 'function') {
                        onfinish();
                    }
                    resolve();
                    return;
                }

                require(['Users'], (Users) => {
                    Users.activate([this.getId()], () => {
                        if (typeof onfinish === 'function') {
                            onfinish();
                        }

                        resolve();
                    }).catch(reject);
                });
            });
        },

        /**
         * Deactivate the user
         *
         * @method classes/users/User#deactivate
         * @param {Function} [onfinish] - (optional), callback function, calls if deactivation is finish
         * @return {Promise}
         */
        deactivate: function (onfinish) {
            return new Promise((resolve, reject) => {
                if (!this.$uid) {
                    if (typeof onfinish === 'function') {
                        onfinish();
                    }
                    resolve();
                    return;
                }

                require(['Users'], (Users) => {
                    Users.deactivate([this.getId()], () => {
                        if (typeof onfinish === 'function') {
                            onfinish();
                        }
                        resolve();
                    }).catch(reject);
                });
            });
        },

        /**
         * Saves a Password to the User
         *
         * @method classes/users/User#deactivate
         * @param {String} pass1 - Password
         * @param {String} pass2 - Password repeat
         * @param {Object} [options]    - (optional),
         * @param {Function} [onfinish] - (optional), callback
         */
        savePassword: function (pass1, pass2, options, onfinish) {
            return new Promise((resolve, reject) => {
                if (!this.$uid) {
                    onfinish(false, false);
                    reject('Unknown User-ID');
                    return;
                }

                options = options || {};

                if (pass1 !== pass2) {
                    if (typeof onfinish === 'function') {
                        onfinish(false, false);
                    }

                    reject(Locale.get('quiqqer/core', 'exception.user.wrong.passwords'));
                    return;
                }

                Ajax.post('ajax_users_set_password', (result) => {
                    this.setAttribute('hasPassword', 1);

                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    resolve(result);
                }, {
                    uid: this.getId(),
                    pw1: pass1,
                    pw2: pass2,
                    params: JSON.encode(options),
                    onError: reject
                });
            });
        },

        /**
         * Is the user activated?
         *
         * @return {Number} 0, 1, -1
         */
        isActive: function () {
            if (!this.$uid) {
                return 0;
            }

            return parseInt(this.getAttribute('active'));
        },

        /**
         * Enable a authenticator for the user
         *
         * @param {String} authenticator - name of the authenticator
         * @returns {Promise}
         */
        enableAuthenticator: function (authenticator) {
            return new Promise((resolve, reject) => {
                Ajax.post('ajax_users_authenticator_enable', resolve, {
                    authenticator: authenticator,
                    uid: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Disable a authenticator for the user
         *
         * @param {String} authenticator - name of the authenticator
         * @returns {Promise}
         */
        disableAuthenticator: function (authenticator) {
            return new Promise((resolve, reject) => {
                Ajax.post('ajax_users_authenticator_disable', resolve, {
                    authenticator: authenticator,
                    uid: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Return the Authenticator settings control
         *
         * @param {String} authenticator - name of the authenticator
         * @returns {Promise}
         */
        getAuthenticatorSettings: function (authenticator) {
            return new Promise((resolve, reject) => {
                Ajax.get('ajax_users_authenticator_settings', resolve, {
                    authenticator: authenticator,
                    uid: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Has the user the authenticator
         *
         * @param {String} authenticator - name of the authenticator
         * @returns {Promise}
         */
        hasAuthenticator: function (authenticator) {
            return new Promise((resolve, reject) => {
                Ajax.post('ajax_users_authenticator_has', resolve, {
                    authenticator: authenticator,
                    uid: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Return the address list from the user
         *
         * @returns {Promise}
         */
        getAddressList: function () {
            return new Promise((resolve, reject) => {
                if (this.$addresses) {
                    resolve(this.$addresses);
                    return;
                }

                Ajax.post('ajax_users_address_list', (result) => {
                    this.$addresses = result;
                    resolve(this.$addresses);
                }, {
                    uid: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Attribute methods
         */

        /**
         * Set an attribute to the Object
         * You can extend the Object with everything you like
         * You can extend the Object width more than the default options
         *
         * @method classes/users/User#setAttribute
         *
         * @param {String} k - Name of the Attribute
         * @param {Object|String|Number|Array} v - value
         *
         * @return {Object} this (classes/users/User)
         */
        setAttribute: function (k, v) {
            this.attributes[k] = v;
            return this;
        },

        /**
         * If you want set more than one attribute
         *
         * @method classes/users/User#setAttribute
         *
         * @param {Object} attributes - Object with attributes
         * @return {Object} this (classes/users/User)
         *
         * @example Object.setAttributes({
         *   attr1 : '1',
         *   attr2 : []
         * })
         */
        setAttributes: function (attributes) {
            attributes = attributes || {};

            for (let k in attributes) {
                if (attributes.hasOwnProperty(k)) {
                    this.setAttribute(k, attributes[k]);
                }
            }

            return this;
        },

        /**
         * Return an attribute of the Object
         * returns not the default attributes, too
         *
         * @method classes/users/User#setAttribute
         *
         * @param {Object} k - Object width attributes
         * @return {Boolean|String} The wanted attribute or false
         */
        getAttribute: function (k) {
            if (typeof this.attributes[k] !== 'undefined') {
                return this.attributes[k];
            }

            return false;
        },

        /**
         * Return the default attributes
         *
         * @method classes/users/User#getAttributes
         * @return {Object} alle attributes
         */
        getAttributes: function () {
            return this.attributes;
        },

        /**
         * Return true if a attribute exist
         *
         * @method classes/users/User#existAttribute
         * @param {String} k - wanted attribute
         * @return {Boolean} true or false
         */
        existAttribute: function (k) {
            return typeof this.attributes[k] !== 'undefined';
        },

        /**
         * Return the extra entry
         *
         * @param {String} field
         * @return {String|Number|Array|Boolean}
         */
        getExtra: function (field) {
            if (typeof this.$extras[field] !== 'undefined') {
                return this.$extras[field];
            }

            return false;
        },

        /**
         * Set a extra attribute
         *
         * @param {String} field - Name of the extra field
         * @param {String|Boolean} value - Value of the extra field
         */
        setExtra: function (field, value) {
            this.$extras[field] = value;
        },

        /**
         * Return all extra attributes
         *
         * @return {Object}
         */
        getExtras: function () {
            return this.$extras;
        }
    });
});
