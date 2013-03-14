
/**
 * Group Manager (Model)
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require classes/DOM
 * @require classes/groups/Group
 *
 * @module classes/groups/Groups
 * @package com.pcsg.qui.js.classes.groups
 * @namespace QUI.classes.groups.Groups
 *
 * @event onActivate [this, result, Request]
 * @event onDeactivate [this, result, Request]
 * @event onDelete [this, gids]
 * @event onRefresh [this, Group]
 *
 */

define('classes/groups/Groups', [

    'classes/DOM',
    'classes/groups/Group'

], function(DOM)
{
    QUI.namespace( 'classes.groups' );

    /**
     * @class QUI.classes.groups.Groups
     *
     * @memberof! <global>
     */
    QUI.classes.groups.Groups = new Class({

        Implements : [ DOM ],
        Type       : 'QUI.classes.groups.Groups',

        $groups : {},

        /**
         * Return a group
         *
         * @return {QUI.classes.groups.Group}
         */
        get : function(gid)
        {
            if ( typeof this.$groups[ gid ] === 'undefined' ) {
                this.$groups[ gid ] = new QUI.classes.groups.Group( gid );
            }

            return this.$groups[ gid ];
        },

        /**
         * Return the group list
         *
         * @param {Object} search     - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        getList : function(search, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                params   : JSON.encode( search ),
                onfinish : onfinish
            });

            QUI.Ajax.get('ajax_groups_search', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, params);
        },

        /**
         * Switch the status to activate or deactivate of the group
         *
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        switchStatus : function(gid, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                Groups   : this,
                gid      : JSON.encode( gid ),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_groups_switchstatus', function(result, Request)
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
         * @method QUI.classes.groups.Groups#activate
         *
         * @param {Array|Integer} gid - group id
         * @param {Function} onfinish - callback function after activasion
         * @param {Object} params     - callback parameter
         */
        activate : function(gid, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                Groups   : this,
                gid      : JSON.encode( gid ),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_groups_activate', function(result, Request)
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
         * @method QUI.classes.groups.Groups#deactivate
         *
         * @param {Array|Integer} gid - group id
         * @param {Function} onfinish - callback function after activasion
         * @param {Object} params     - callback parameter
         */
        deactivate : function(gid, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                Groups   : this,
                gid      : JSON.encode( gid ),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_groups_deactivate', function(result, Ajax)
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
         * @param {String} groupname  - Name of the group
         * @param {Inetegr} parentid  - ID of the parent group
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        createGroup : function(groupname, parentid, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                groupname : groupname,
                pid       : parentid,
                onfinish  : onfinish
            });

            QUI.Ajax.post('ajax_groups_create', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }
            }, params);
        },

        /**
         * Delete groups
         *
         * @param {Array} gids - Group-IDs
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        deleteGroups : function(gids, onfinish, params)
        {
            params = QUI.lib.Utils.combine(params, {
                gids     : JSON.encode( gids ),
                onfinish : onfinish,
                Groups   : this
            });

            QUI.Ajax.post('ajax_groups_delete', function(result, Request)
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
         * @param {QUI.classes.groups.Group} Group
         */
        refreshGroup : function(Group)
        {
            this.fireEvent( 'refresh', [ this, Group ] );
        }
    });

    return QUI.classes.groups.Groups;
});
