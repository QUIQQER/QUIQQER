/**
 * User Manager - class
 *
 * @module classes/users/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onSwitchStatus [this, result, Request]
 * @event onActivate [this, result, Request]
 * @event onDeactivate [this, result, Request]
 * @event onDelete [this, uids]
 * @event onRefresh [this, User]
 * @event onSave [this, User]
 */
define('classes/users/Manager', [

    'qui/classes/DOM',
    'classes/users/User',
    'classes/users/Nobody',
    'classes/users/SystemUser',
    'Ajax',
    'qui/utils/Object'

], function(DOM, User, Nobody, SystemUser, Ajax, ObjectUtils) {
    'use strict';

    /**
     * @class classes/users/Manager
     * @desc User Manager (Model)
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: DOM,                   // @member classes/users/Manager
        Type: 'classes/users/Users', // @member classes/users/Manager
        $users: {},                    // @member classes/users/Manager

        /**
         * Return a user
         *
         * @method classes/users/Manager#get
         * @param {Number|String} uid - Id of the User
         * @return {Object} User - controls/users/User
         */
        get: function(uid) {
            if (uid === 0 || uid === false || uid === '0') {
                return new Nobody();
            }

            if (uid === 5 || uid === '5') {
                return new SystemUser();
            }

            if (typeof this.$users[uid] === 'undefined') {
                this.$users[uid] = new User(uid);
            }

            return this.$users[uid];
        },

        /**
         * Return the loged in user (session user)
         *
         * @method classes/users/Manager#getUserBySession
         * @return {Object} User - controls/users/User
         */
        getUserBySession: function() {
            if (typeof this.$users[USER.id] === 'undefined') {
                this.$users[USER.id] = new User(USER.id);
            }

            return this.$users[USER.id];
        },

        /**
         * Return the user list
         *
         * @method classes/users/Manager#getList
         * @param {Object} search     - search options
         * @param {Object} [params]     - (optional), extra params
         * @return {Promise}
         */
        getList: function(search, params) {
            return new Promise(function(resolve, reject) {
                Ajax.get('ajax_users_search', resolve, ObjectUtils.combine(params, {
                    params: JSON.encode(search),
                    onError: reject
                }));
            });
        },

        /**
         * Is the mixed an user object?
         *
         * @param {unknown} User
         * @return {boolean}
         */
        isUser: function(User) {
            var type = typeOf(User);

            if (type === 'classes/users/Nobody') {
                return true;
            }

            if (type === 'classes/users/SystemUser') {
                return true;
            }

            return type === 'classes/users/User';
        },

        /**
         * Switch the status to activate or deactivate from an user
         *
         * @method classes/users/Manager#switchStatus
         * @param {Array|Number} uid    - search options
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params]     - (optional), extra params
         * @return Promise
         */
        switchStatus: function(uid, onfinish, params) {
            var self = this;

            return new Promise(function(resolve, reject) {
                params = ObjectUtils.combine(params, {
                    uid: JSON.encode(uid),
                    onError: reject
                });

                Ajax.post('ajax_users_switchstatus', function(result) {
                    if (uid in result && uid in self.$users) {
                        self.$users[uid].setAttribute('active', result[uid]);
                    }

                    if (typeof onfinish !== 'undefined') {
                        onfinish(result);
                    }

                    resolve(self, result);

                    self.fireEvent('switchStatus', [self, result]);
                }, params);
            });
        },

        /**
         * Activate the user / users
         *
         * @method classes/users/Manager#activate
         * @param {Array|Number} uid - search options
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params]     - (optional), extra params
         * @return Promise
         */
        activate: function(uid, onfinish, params) {
            var self = this;

            return new Promise(function(resolve, reject) {
                params = ObjectUtils.combine(params, {
                    uid: JSON.encode(uid),
                    onError: reject
                });

                Ajax.post('ajax_users_activate', function(result) {
                    if (uid in result && uid in self.$users) {
                        self.$users[uid].setAttribute('active', result[uid]);
                    }

                    if (typeof onfinish !== 'undefined') {
                        onfinish(result);
                    }

                    resolve(self, result);

                    self.fireEvent('activate', [self, result]);
                    self.fireEvent('switchStatus', [self, result]);
                }, params);
            });
        },

        /**
         * Deactivate the user / users
         *
         * @method classes/users/Manager#deactivate
         * @param {Array|Number} uid    - search options
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params]     - (optional), extra params
         * @return Promise
         */
        deactivate: function(uid, onfinish, params) {
            var self = this;

            return new Promise(function(resolve, reject) {
                params = ObjectUtils.combine(params, {
                    uid: JSON.encode(uid),
                    onError: reject
                });

                Ajax.post('ajax_users_deactivate', function(result) {
                    if (uid in result && uid in self.$users) {
                        self.$users[uid].setAttribute('active', result[uid]);
                    }

                    if (typeof onfinish !== 'undefined') {
                        onfinish(result, Request);
                    }

                    resolve(self, result);

                    self.fireEvent('deactivate', [self, result]);
                    self.fireEvent('switchStatus', [self, result]);
                }, params);
            });
        },

        /**
         * Checks if the username exists
         *
         * @method classes/users/Manager#existsUsername
         * @param {String} username   - Username
         * @param {Function} onfinish - callback function
         * @param {Object} [params]   - (optional), extra params
         */
        existsUsername: function(username, onfinish, params) {
            params = ObjectUtils.combine(params, {
                username: username
            });

            Ajax.get('ajax_users_exists', function(result, Request) {
                onfinish(result, Request);
            }, params);
        },

        /**
         * create a new user
         *
         * @method classes/users/Manager#createUser
         * @param {String} username     - Username
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params]     - (optional), extra params
         */
        createUser: function(username, onfinish, params) {
            params = ObjectUtils.combine(params, {
                username: username
            });

            Ajax.post('ajax_users_create', function(result, Request) {
                if (typeof onfinish !== 'undefined') {
                    onfinish(result, Request);
                }
            }, params);
        },

        /**
         * Delete users
         *
         * @method classes/users/Manager#deleteUsers
         * @param {Array} uids          - User-IDs
         * @param {Object} [params]     - (optional), extra params
         * @param {Function} [onfinish] - (optional), callback function
         */
        deleteUsers: function(uids, params, onfinish) {
            return new Promise(function(resolve) {
                params = ObjectUtils.combine(params, {
                    uid: JSON.encode(uids)
                });

                Ajax.post('ajax_users_delete', function(result) {
                    for (var i = 0, len = uids.length; i < len; i++) {
                        if (typeof this.$users[uids[i]] !== 'undefined') {
                            delete this.$users[uids[i]];
                        }
                    }

                    this.fireEvent('delete', [this, uids]);

                    if (typeof onfinish !== 'undefined') {
                        onfinish(result);
                    }

                    resolve(result);

                }.bind(this), params);
            }.bind(this));
        },

        /**
         * Triggerd by an user
         *
         * @method classes/users/Manager#onRefreshUser
         * @param {Object} User - controls/users/User
         */
        onRefreshUser: function(User) {
            this.fireEvent('refresh', [this, User]);
        },

        /**
         * Check if the given user has an email address
         *
         * @param {Number} userId
         * @return {Promise}
         */
        hasEmail: function(userId) {
            return new Promise(function(resolve, reject) {
                Ajax.get('ajax_users_hasEmail', resolve, {
                    userId: userId,
                    onError: reject
                });
            });
        },

        /**
         * Save a user with its attributes and rights
         *
         * @method classes/users/Manager#saveUser
         * @param {Object} User         - controls/users/User
         * @param {Object} [params]     - (optional), extra params
         * @param {Function} [onfinish] - (optional), callback
         * @return {Promise}
         */
        saveUser: function(User, params, onfinish) {
            return new Promise(function(resolve, reject) {
                var self = this,
                    attributes = User.getAttributes();

                for (var i in attributes) {
                    if (!attributes.hasOwnProperty(i)) {
                        continue;
                    }

                    if (typeof attributes[i] === 'object') {
                        delete attributes[i];
                    }
                }

                // attributes.extra = User.getExtras();
                params = ObjectUtils.combine(params, {
                    uid: User.getId(),
                    attributes: JSON.encode(attributes),
                    onError: reject
                });

                Ajax.post('ajax_users_save', function(result, Request) {
                    self.get(User.getId());
                    self.fireEvent('save', [self, User]);

                    if (typeof onfinish !== 'undefined') {
                        onfinish(User, Request);
                    }

                    resolve();

                }, params);
            }.bind(this));
        }
    });
});
