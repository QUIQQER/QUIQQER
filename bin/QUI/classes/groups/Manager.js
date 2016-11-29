/**
 * Group Manager (Model)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module classes/groups/Manager
 *
 * @require qui/classes/DOM
 * @require classes/groups/Group
 * @require Ajax
 * @require qui/utils/Object
 *
 * @event onActivate [this, result, Request]
 * @event onDeactivate [this, result, Request]
 * @event onDelete [this, gids]
 * @event onRefresh [this, Group]
 */
define('classes/groups/Manager', [

    'qui/classes/DOM',
    'classes/groups/Group',
    'Ajax',
    'qui/utils/Object'

], function (QDOM, Group, Ajax, Utils) {
    "use strict";

    /**
     * Group Manager
     * @class classes/groups/Manager
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QDOM,
        Type   : 'classes/groups/Manager',

        $groups: {},

        /**
         * Return a group
         *
         * @method classes/groups/Manager#get
         * @return {Object} Group - classes/groups/Group
         */
        get: function (gid) {
            if (typeof this.$groups[gid] === 'undefined') {
                this.$groups[gid] = new Group(gid);
            }

            return this.$groups[gid];
        },

        /**
         * Return the group list
         *
         * @method classes/groups/Manager#getList
         * @param {Object} search       - search options
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params]     - (optional), extra params
         * @return {Promise}
         */
        getList: function (search, params, onfinish) {
            params = Utils.combine(params, {
                params: JSON.encode(search)
            });

            return new Promise(function (resolve) {
                Ajax.get('ajax_groups_list', function (result) {
                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    resolve(result);
                }, params);
            });
        },

        /**
         * Search groups
         *
         * @returns {Promise}
         *
         * @example
         *
         * Groups.search({
         *     order: 'ASC',
         *     limit: 5
         * }, {
         *     id  : value,
         *     name: value
         * }).then(function (result) {
         *
         *
         * });
         */
        search: function (params, fields) {
            params = params || {};
            fields = fields || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_groups_search', resolve, {
                    onError: reject,
                    fields : JSON.encode(fields),
                    params : JSON.encode(params)
                });
            });
        },

        /**
         * Switch the status to activate or deactivate of the group
         *
         * @method classes/groups/Manager#switchStatus
         * @param {Array|Number} gid    - search options
         * @param {Object} [params]     - (optional), extra params
         * @param {Function} [onfinish] - (optional), callback function
         */
        switchStatus: function (gid, params, onfinish) {
            params     = params || {};
            params.gid = JSON.encode(gid);

            return new Promise(function (resolve) {
                Ajax.post('ajax_groups_switchstatus', function (result) {

                    // groups refresh if the object exist
                    for (var id in result) {
                        if (!result.hasOwnProperty(id)) {
                            continue;
                        }

                        if (this.$groups[id]) {
                            this.$groups[id].setAttribute('active', parseInt(result[id]));
                        }
                    }

                    this.fireEvent('switchStatus', [this, result]);

                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    resolve(result);

                }.bind(this), params);
            }.bind(this));
        },

        /**
         * Activate a group
         *
         * @method classes/groups/Manager#activate
         * @param {Array|Number} gid - group id
         * @param {Object} params     - callback parameter
         * @param {Function} onfinish - callback function after activasion
         * @return {Promise}
         */
        activate: function (gid, params, onfinish) {
            params     = params || {};
            params.gid = JSON.encode(gid);

            return new Promise(function (resolve) {

                Ajax.post('ajax_groups_activate', function (result) {
                    // groups refresh if the object exist
                    for (var id in result) {
                        if (!result.hasOwnProperty(id)) {
                            continue;
                        }

                        if (this.$groups[id]) {
                            this.$groups[id].setAttribute('active', parseInt(result[id]));
                        }
                    }

                    this.fireEvent('activate', [this, result]);

                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    resolve(result);

                }.bind(this), params);

            }.bind(this));
        },

        /**
         * Dectivate a group
         *
         * @method classes/groups/Manager#deactivate
         * @param {Array|Number} gids - group id
         * @param {Object} params     - callback parameter
         * @param {Function} onfinish - callback function after activasion
         */
        deactivate: function (gids, params, onfinish) {
            params     = params || {};
            params.gid = JSON.encode(gids);

            return new Promise(function (resolve) {
                Ajax.post('ajax_groups_deactivate', function (result) {

                    // groups refresh if the object exist
                    for (var id in result) {
                        if (!result.hasOwnProperty(id)) {
                            continue;
                        }

                        if (this.$groups[id]) {
                            this.$groups[id].setAttribute('active', parseInt(result[id]));
                        }
                    }

                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    this.fireEvent('deactivate', [this, result]);

                    resolve();

                }.bind(this), params);
            }.bind(this));
        },

        /**
         * create a new group
         *
         * @method classes/groups/Manager#createGroup
         * @param {String} groupname  - Name of the group
         * @param {Number} parentid  - ID of the parent group
         * @param {Object} [params]     - (optional), extra params
         * @param {Function} [onfinish] - (optional), callback function
         * @return {Promise}
         */
        createGroup: function (groupname, parentid, params, onfinish) {
            params = Utils.combine(params, {
                groupname: groupname,
                pid      : parentid
            });

            return new Promise(function (resolve) {
                Ajax.post('ajax_groups_create', function (result) {
                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    resolve(result);
                }, params);
            });
        },

        /**
         * Delete groups
         *
         * @method classes/groups/Manager#deleteGroups
         * @param {Array} gids - Group-IDs
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params]     - (optional), extra params
         * @return {Promise}
         */
        deleteGroups: function (gids, params, onfinish) {
            params      = params || {};
            params.gids = JSON.encode(gids);

            return new Promise(function (resolve) {

                Ajax.post('ajax_groups_delete', function (result) {
                    for (var i = 0, len = result.length; i < len; i++) {
                        if (typeof this.$groups[gids[i]] !== 'undefined') {
                            delete this.$groups[gids[i]];
                        }
                    }

                    this.fireEvent('delete', [this, gids]);

                    if (typeof onfinish === 'function') {
                        onfinish(gids);
                    }

                    resolve(gids);

                }.bind(this), params);
            }.bind(this));
        },

        /**
         * Add user(s) to a group
         *
         * @param {number} gid - Group ID
         * @param {array} userIds - IDs of users that shall be added to the group
         * @returns {Promise}
         */
        addUsers: function (gid, userIds) {
            return new Promise(function (resolve) {
                Ajax.post('ajax_groups_addUsers', resolve, {
                    gid    : gid,
                    userIds: JSON.encode(userIds)
                });
            });
        },

        /**
         * Trigger the refresh event
         *
         * @method classes/groups/Manager#refreshGroup
         * @param {Object} Group - classes/groups/Group
         */
        refreshGroup: function (Group) {
            this.fireEvent('refresh', [this, Group]);
        }
    });
});
