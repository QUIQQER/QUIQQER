
/**
 * Group Manager (Model)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module classes/groups/Manager
 *
 * @require classes/DOM
 * @require classes/groups/Group
 *
 * @event onActivate [this, result, Request]
 * @event onDeactivate [this, result, Request]
 * @event onDelete [this, gids]
 * @event onRefresh [this, Group]
 */

define([

    'qui/classes/DOM',
    'classes/groups/Group',
    'Ajax',
    'qui/utils/Object'

], function(QDOM, Group, Ajax, Utils)
{
    "use strict";

    /**
     * Group Manager
     * @class classes/groups/Manager
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QDOM,
        Type    : 'classes/groups/Manager',

        $groups : {},

        /**
         * Return a group
         *
         * @method classes/groups/Manager#get
         * @return {classes/groups/Group} Group
         */
        get : function(gid)
        {
            if ( typeof this.$groups[ gid ] === 'undefined' ) {
                this.$groups[ gid ] = new Group( gid );
            }

            return this.$groups[ gid ];
        },

        /**
         * Return the group list
         *
         * @method classes/groups/Manager#getList
         * @param {Object} search     - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        getList : function(search, onfinish, params)
        {
            params = Utils.combine(params, {
                params   : JSON.encode( search ),
                onfinish : onfinish
            });

            Ajax.get('ajax_groups_search', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, params);
        },

        /**
         * Switch the status to activate or deactivate of the group
         *
         * @method classes/groups/Manager#switchStatus
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        switchStatus : function(gid, onfinish, params)
        {
            params = Utils.combine(params, {
                Groups   : this,
                gid      : JSON.encode( gid ),
                onfinish : onfinish
            });

            Ajax.post('ajax_groups_switchstatus', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Groups = Request.getAttribute( 'Groups' );

                // groups refresh if the object exist
                for ( var id in result )
                {
                    if ( Groups.$groups[ id ] )
                    {
                        Groups.$groups[ id ].setAttribute(
                            'active',
                            ( result[id] ).toInt()
                        );
                    }
                }

                Groups.fireEvent( 'switchStatus', [ Groups, result, Request ] );

            }, params);
        },

        /**
         * Activate a group
         *
         * @method classes/groups/Manager#activate
         * @param {Array|Integer} gid - group id
         * @param {Function} onfinish - callback function after activasion
         * @param {Object} params     - callback parameter
         */
        activate : function(gid, onfinish, params)
        {
            params = Utils.combine(params, {
                Groups   : this,
                gid      : JSON.encode( gid ),
                onfinish : onfinish
            });

            Ajax.post('ajax_groups_activate', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Groups = Request.getAttribute( 'Groups' );

                // groups refresh if the object exist
                for ( var id in result )
                {
                    if ( Groups.$groups[ id ] )
                    {
                        Groups.$groups[ id ].setAttribute(
                            'active',
                            ( result[id] ).toInt()
                        );
                    }
                }

                Groups.fireEvent( 'activate', [ Groups, result, Request ] );

            }, params);
        },

        /**
         * Dectivate a group
         *
         * @method classes/groups/Manager#deactivate
         * @param {Array|Integer} gid - group id
         * @param {Function} onfinish - callback function after activasion
         * @param {Object} params     - callback parameter
         */
        deactivate : function(gid, onfinish, params)
        {
            params = Utils.combine(params, {
                Groups   : this,
                gid      : JSON.encode( gid ),
                onfinish : onfinish
            });

            Ajax.post('ajax_groups_deactivate', function(result, Ajax)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Groups = Request.getAttribute( 'Groups' );

                // groups refresh if the object exist
                for ( var id in result )
                {
                    if ( Groups.$groups[ id ] )
                    {
                        Groups.$groups[ id ].setAttribute(
                            'active',
                            ( result[id] ).toInt()
                        );
                    }
                }

                Groups.fireEvent( 'deactivate', [ Groups, result, Request ] );

            }, params);
        },

        /**
         * create a new group
         *
         * @method classes/groups/Manager#createGroup
         * @param {String} groupname  - Name of the group
         * @param {Inetegr} parentid  - ID of the parent group
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        createGroup : function(groupname, parentid, onfinish, params)
        {
            params = Utils.combine(params, {
                groupname : groupname,
                pid       : parentid,
                onfinish  : onfinish
            });

            Ajax.post('ajax_groups_create', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }
            }, params);
        },

        /**
         * Delete groups
         *
         * @method classes/groups/Manager#deleteGroups
         * @param {Array} gids - Group-IDs
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        deleteGroups : function(gids, onfinish, params)
        {
            params = Utils.combine(params, {
                gids     : JSON.encode( gids ),
                onfinish : onfinish,
                Groups   : this
            });

            Ajax.post('ajax_groups_delete', function(result, Request)
            {
                var Groups = Request.getAttribute( 'Groups' );

                for ( var i = 0, len = gids.length; i < len; i++ )
                {
                    if ( typeof Groups.$groups[ gids[ i ] ] !== 'undefined' ) {
                        delete Groups.$groups[ gids[ i ] ];
                    }
                }

                Groups.fireEvent( 'delete', [ this, gids ] );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( gids, Request );
                }
            }, params);
        },

        /**
         * Trigger the refresh event
         *
         * @method classes/groups/Manager#refreshGroup
         * @param {classes/groups/Group} Group
         */
        refreshGroup : function(Group)
        {
            this.fireEvent( 'refresh', [ this, Group ] );
        }
    });
});
