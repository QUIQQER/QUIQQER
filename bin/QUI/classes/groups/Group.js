
/**
 * Group (Model)
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require Ajax
 * @require qui/utils/Object
 *
 * @module classes/groups/Group
 * @package com.pcsg.qui.js.classes.groups
 *
 * @event onRefresh [ {classes/groups/Group} ]
 * @event onActivate [ {classes/groups/Group} ]
 * @event onDeactivate [ {classes/groups/Group} ]
 */

define('classes/groups/Group', [

    'qui/classes/DOM',
    'Ajax',
    'qui/utils/Object'

], function(DOM, Ajax, ObjectUtils)
{
    "use strict";

    /**
     * A QUIQQER Group
     *
     * @class classes/groups/Group
     * @param {Integer} gid - Group-ID
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/groups/Group',

        attributes : {}, // group attributes

        initialize : function(gid)
        {
            this.$gid = gid;
        },

        /**
         * Return the Group-ID
         *
         * @method classes/groups/Group#getId
         * @return {Integer} Group-ID
         */
        getId : function()
        {
            return this.$gid;
        },

        /**
         * Load the group attributes from the db
         *
         * @method classes/groups/Group#load
         * @param {Function} onfinish - [optional] callback
         */
        load: function(onfinish)
        {
            Ajax.get('ajax_groups_get', function(result, Request)
            {
                var Group = Request.getAttribute( 'Group' );

                Group.setAttributes( result );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( Group, Request );
                }

                Group.fireEvent( 'refresh', [ Group ] );

            }, {
                gid      : this.getId(),
                Group    : this,
                onfinish : onfinish
            });
        },

        /**
         * Return the children groups of the group
         *
         * @method classes/groups/Group#load
         * @param {Function} onfinish - [optional] callback
         * @param {Object} params - [optional] binded params at the request
         */
        getChildren : function(onfinish, params)
        {
            params = ObjectUtils.combine(params, {
                gid      : this.getId(),
                Group    : this,
                onfinish : onfinish
            });

            Ajax.get('ajax_groups_children', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                     Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, params);
        },

        /**
         * Save the group with its actualy attributes
         *
         * @method classes/groups/Group#save
         * @param {Function} onfinish - [optional] callback
         * @param {Object} params - [optional] binded params at the request
         */
        save : function(onfinish, params)
        {
            params = ObjectUtils.combine(params, {
                gid        : this.getId(),
                Group      : this,
                onfinish   : onfinish,
                attributes : JSON.encode( this.getAttributes() ),
                rights     : JSON.encode( this.getRights() )
            });

            Ajax.post('ajax_groups_save', function(result, Request)
            {
                var Group = Request.getAttribute( 'Group' );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( Group, Request );
                }

                Group.fireEvent( 'refresh', [ Group ] );
                QUI.Groups.refreshGroup( Group );

            }, params);
        },

        /**
         * Is the Group active?
         *
         * @method classes/groups/Group#isActive
         * @return {Bool} true or false
         */
        isActive : function()
        {
            return ( this.getAttribute( 'active' ) ).toInt() ? true : false;
        },

        /**
         * Get all users that are inside the group
         *
         * @method classes/groups/Group#getUsers
         * @param {Function} onfinish - Callback function
         *         the return of the function: {Array}
         * @param {Object} params - limit params (limit, page, field, order)
         *
         * @return {this} self
         */
        getUsers : function(onfinish, limits)
        {
            var params = {
                limit : limits.limit || 50,
                page  : limits.page  || 1,
                field : limits.field || 'name',
                order : limits.order || 'DESC'
            };

            Ajax.get('ajax_groups_users', function(result, Request)
            {
                Request.getAttribute('onfinish')( result, Request );
            }, {
                gid      : this.getId(),
                params   : JSON.encode( params ),
                onfinish : onfinish
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
         * @param {Object|String|Integer|Array} v - value
         * @return {this} self
         */
        setAttribute : function(k, v)
        {
            this.attributes[ k ] = v;
            return this;
        },

        /**
         * If you want set more than one attribute
         *
         * @method classes/groups/Group#setAttribute
         *
         * @param {Object} attributes - Object with attributes
         * @return {this} self
         *
         * @example Object.setAttributes({
         *   attr1 : '1',
         *   attr2 : []
         * })
         */
        setAttributes : function(attributes)
        {
            attributes = attributes || {};

            for ( var k in attributes ) {
                this.setAttribute( k, attributes[ k ] );
            }

            return this;
        },

        /**
         * Return an attribute of the Object
         * returns the not the default attributes, too
         *
         * @method classes/groups/Group#setAttribute
         * @param {Object} attributes - Object width attributes
         * @return {unknown_type|Bool} wanted attribute
         */
        getAttribute : function(k)
        {
            if ( typeof this.attributes[ k ] !== 'undefined' ) {
                return this.attributes[ k ];
            }

            return false;
        },

        /**
         * Return the default attributes
         *
         * @method classes/groups/Group#getAttributes
         * @return {Object} all attributes
         */
        getAttributes : function()
        {
            return this.attributes;
        },

        /**
         * Return true if a attribute exist
         *
         * @method classes/groups/Group#existAttribute
         * @param {String} k - wanted attribute
         * @return {Bool} true | false
         */
        existAttribute : function(k)
        {
            if ( typeof this.attributes[ k ] !== 'undefined' ) {
                return true;
            }

            return false;
        }
    });
});
