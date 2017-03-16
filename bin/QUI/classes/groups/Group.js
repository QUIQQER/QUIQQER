/**
 * Group (Model)
 *
 * @module classes/groups/Group
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require Ajax
 * @require qui/utils/Object
 *
 * @event onRefresh [ {classes/groups/Group} ]
 * @event onActivate [ {classes/groups/Group} ]
 * @event onDeactivate [ {classes/groups/Group} ]
 */
define('classes/groups/Group', [

    'qui/classes/DOM',
    'qui/utils/Object',
    'Ajax'

], function (DOM, ObjectUtils, Ajax) {
    "use strict";

    /**
     * A QUIQQER Group
     *
     * @class classes/groups/Group
     * @param {Number} gid - Group-ID
     * @memberof! <global>
     */
    return new Class({

        Extends: DOM,
        Type   : 'classes/groups/Group',

        attributes: {}, // group attributes

        initialize: function (gid) {
            this.$gid    = gid;
            this.$loaded = false;
        },

        /**
         * Return the Group-ID
         *
         * @method classes/groups/Group#getId
         * @return {Number} Group-ID
         */
        getId: function () {
            return this.$gid;
        },

        /**
         * Return the group name
         *
         * @method classes/groups/Group#getName
         * @return {String} Groupname
         */
        getName: function () {
            return this.getAttribute('name');
        },

        /**
         * Load the group attributes from the db
         *
         * @method classes/groups/Group#load
         * @param {Function} [onfinish] - (optional), callback
         * @return {Promise}
         */
        load: function (onfinish) {
            var self = this;

            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_groups_get', function (result) {
                    self.setAttributes(result);
                    self.$loaded = true;

                    if (typeof onfinish === 'function') {
                        onfinish(self);
                    }

                    resolve(self);

                    self.fireEvent('refresh', [self]);
                }, {
                    gid    : self.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Activate the group
         *
         * @returns {Promise}
         */
        activate: function () {
            return new Promise(function (resolve) {
                require(['Groups'], function (Groups) {
                    return Groups.activate(this.getId()).then(resolve);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Deactivate the group
         *
         * @returns {Promise}
         */
        deactivate: function () {
            return new Promise(function (resolve) {
                require(['Groups'], function (Groups) {
                    return Groups.deactivate(this.getId()).then(resolve);
                }.bind(this));
            }.bind(this));
        },

        /**
         * the group has been loaded once?
         *
         * @return {Boolean}
         */
        isLoaded: function () {
            return this.$loaded;
        },

        /**
         * Return the children groups of the group
         *
         * @method classes/groups/Group#load
         * @param {Function} [onfinish] - (optional), callback
         * @param {Object} [params] - (optional), binded params at the request
         * @return {Promise}
         */
        getChildren: function (onfinish, params) {
            return new Promise(function (resolve) {
                params = ObjectUtils.combine(params, {
                    gid: this.getId()
                });

                Ajax.get('ajax_groups_children', function (result) {
                    if (typeof onfinish !== 'undefined') {
                        onfinish(result);
                    }

                    resolve(result);
                }, params);
            }.bind(this));
        },

        /**
         * Save the group with its actualy attributes
         *
         * @method classes/groups/Group#save
         * @param {Function} [onfinish] - (optional), callback
         * @param {Object} [params] - (optional), binded params at the request
         * @return {Promise}
         */
        save: function (onfinish, params) {
            var self = this;

            params = ObjectUtils.combine(params, {
                gid       : this.getId(),
                attributes: JSON.encode(this.getAttributes()),
                rights    : '[]' //JSON.encode( this.getRights() )
            });

            return new Promise(function (resolve) {
                Ajax.post('ajax_groups_save', function () {
                    if (typeof onfinish !== 'undefined') {
                        onfinish(self);
                    }

                    resolve(self);

                    self.fireEvent('refresh', [self]);

                    require(['Groups'], function (Groups) {
                        Groups.refreshGroup(self);
                    });
                }, params);
            });
        },

        /**
         * Is the Group active?
         *
         * @method classes/groups/Group#isActive
         * @return {Boolean} true or false
         */
        isActive: function () {
            return !!(this.getAttribute('active')).toInt();
        },

        /**
         * Get all users that are inside the group
         *
         * @method classes/groups/Group#getUsers
         * @param {Function} onfinish - Callback function
         *         the return of the function: {Array}
         * @param {Object} limits - limit params (limit, page, field, order)
         *
         * @return {Object} this (classes/groups/Group)
         */
        getUsers: function (onfinish, limits) {
            var params = {
                limit: limits.limit || 50,
                page : limits.page || 1,
                field: limits.field || 'name',
                order: limits.order || 'DESC'
            };

            Ajax.get('ajax_groups_users', function (result, Request) {
                onfinish(result, Request);
            }, {
                gid   : this.getId(),
                params: JSON.encode(params)
            });

            return this;
        },

        /**
         * Attribute methods
         */

        /**
         * Set an attribute to the Object
         * You can extend the Object with everything you like
         * You can extend the Object width more than the default options
         *
         * @method classes/groups/Group#setAttribute
         * @param {String} k - Name of the Attribute
         * @param {Object|String|Number|Array} v - value
         * @return {Object} this (classes/groups/Group)
         */
        setAttribute: function (k, v) {
            this.attributes[k] = v;
            return this;
        },

        /**
         * If you want set more than one attribute
         *
         * @method classes/groups/Group#setAttribute
         *
         * @param {Object} [attributes] - Object with attributes
         * @return {Object} this (classes/groups/Group)
         *
         * @example Object.setAttributes({
         *   attr1 : '1',
         *   attr2 : []
         * })
         */
        setAttributes: function (attributes) {
            attributes = attributes || {};

            for (var k in attributes) {
                if (attributes.hasOwnProperty(k)) {
                    this.setAttribute(k, attributes[k]);
                }
            }

            return this;
        },

        /**
         * Return an attribute of the Object
         * returns the not the default attributes, too
         *
         * @method classes/groups/Group#setAttribute
         * @param {Object} k - Object width attributes
         * @return {Boolean|Number|Object|Array|String} wanted attribute
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
         * @method classes/groups/Group#getAttributes
         * @return {Object} all attributes
         */
        getAttributes: function () {
            return this.attributes;
        },

        /**
         * Return true if a attribute exist
         *
         * @method classes/groups/Group#existAttribute
         * @param {String} k - wanted attribute
         * @return {Boolean} true | false
         */
        existAttribute: function (k) {
            return typeof this.attributes[k] !== 'undefined';
        }
    });
});
