
/**
 * A project Site Object
 *
 * @module classes/projects/project/Site
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/Control
 * @require controls/contextmenu/Menu
 * @require controls/contextmenu/Item
 *
 * @event onLoad [ this ]
 * @event onGetChildren [ this, {Array} ]
 * @event onActivate [ this ]
 * @event onDeactivate [ this ]
 * @event onDelete [ this ]
 * @event createChild [ this ]
 * @event sortSave [ this ] --> triggerd by SiteChildren.js
 */

define([

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function(QUI, DOM, Ajax)
{
    "use strict";

    /**
     * @class classes/projects/project/Site
     *
     * @param {classes/projects/Project} Project
     * @param {Integer} id - Site ID
     *
     * @fires onStatusEditBegin - this
     * @fires onStatusEditEnd   - this
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/projects/project/Site',

        Binds : [
            'setAttributes',
            'setAttribute',
            'getAttributes',
            'getAttribute'
        ],

        options : {
            Project    : '',
            id         : 0,
            attributes : {}
        },

        initialize : function(Project, id)
        {
            this.$Project      = Project;
            this.$has_children = false;
            this.$parentid     = false;

            this.parent({
                id : id
            });
        },

        /**
         * Load the site
         * Get all attributes from the DB
         *
         * @method classes/projects/project/Site#load
         * @param {Function} onfinish - [optional] callback Function
         * @return {this} self
         */
        load : function(onfinish)
        {
            var params = this.ajaxParams(),
                Site   = this;

            Ajax.get('ajax_site_get', function(result, Request)
            {
                Site.setAttributes( result.attributes );

                Site.$has_children = result.has_children || false;
                Site.$parentid     = result.parentid || false;

                Site.fireEvent( 'load', [ Site ] );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( Site, Request );
                }
            }, params);

            return this;
        },

        /**
         * Get the site ID
         *
         * @method classes/projects/project/Site#getId
         * @return {Integer}
         */
        getId : function()
        {
            return this.getAttribute( 'id' );
        },

        /**
         * Get the site project
         *
         * @method classes/projects/project/Site#getProject
         * @return {classes/projects/Project}
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Has the site children
         *
         * @return {Boolean}
         */
        hasChildren : function()
        {
            return this.$has_children ? true : false;
        },

        /**
         * Return the children count
         *
         * @param {Integer}
         */
        countChild : function()
        {
            return ( this.$has_children ).toInt();
        },

        /**
         * Get the children
         *
         * @method classes/projects/project/Site#getChildren
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params - [optional]
         * @returns {this}
         */
        getChildren : function(onfinish, params)
        {
            var data = this.ajaxParams(),
                Site = this;

            data.params = JSON.encode( params || {} );

            Ajax.get('ajax_site_getchildren', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                Site.fireEvent( 'getChildren', [ Site, result ] );

            }, data);

            return this;
        },

        /**
         * Return the parent
         *
         * @method classes/projects/project/Site#getParent
         * @return {classes/projects/project/Site|false}
         */
        getParent : function()
        {
            if ( !this.$parentid ) {
                return false;
            }

            return this.getProject().get( this.$parentid );
        },

        /**
         * Activate the site
         *
         * @method classes/projects/project/Site#ajaxParams
         * @fires activate
         * @param {Function} onfinish - [optional] callback function
         * @return {this}
         */
        activate : function(onfinish)
        {
            var Site = this;

            Ajax.post('ajax_site_activate', function(result, Request)
            {
                Site.setAttribute( 'active', 1 );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                Site.fireEvent( 'activate', [ Site ] );

            }, this.ajaxParams());

            return this;
        },

        /**
         * Deactivate the site
         *
         * @method classes/projects/project/Site#deactivate
         * @fires deactivate
         * @param {Function} onfinish - [optional] callback function
         * @return {this}
         */
        deactivate : function(onfinish)
        {
            var Site = this;

            Ajax.post('ajax_site_deactivate', function(result, Request)
            {
                Site.setAttribute( 'active', 0 );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                Site.fireEvent( 'deactivate', [ Site ] );
            }, this.ajaxParams());

            return this;
        },

        /**
         * Save the site
         *
         * @method classes/projects/project/Site#save
         * @fires save
         * @param {Function} onfinish - [optional] callback function
         * @return {this}
         */
        save : function(onfinish)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.attributes = JSON.encode( this.getAttributes() );

            Ajax.post('ajax_site_save', function(result, Request)
            {
                if ( result && result.attributes ) {
                    Site.setAttributes( result.attributes );
                }

                Site.$has_children = result && result.has_children || false;
                Site.$parentid     = result && result.parentid || false;

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                Site.fireEvent( 'save', [ Site ] );

            }, params);

            return this;
        },

        /**
         * Delete the site
         * Delete it in the Database, too
         *
         * @method classes/projects/project/Site#del
         * @param {Function} onfinish - [optional] callback function
         */
        del : function(onfinish)
        {
            var Site = this;

            Ajax.post('ajax_site_delete', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                Site.fireEvent( 'delete', [ Site ] );

            }, this.ajaxParams());
        },

        /**
         * Move the site to another parent site
         *
         * @param {Integer} newParentId - ID of the new parent
         * @param {Function} onfinish - [optional] callback function
         */
        move : function(newParentId, callback)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.newParentId = newParentId;

            Ajax.post('ajax_site_move', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }

                Site.fireEvent( 'move', [ Site, newParentId ] );

            }, params);
        },


        /**
         * Copy the site to another parent site
         *
         * @param {Integer} newParentId - ID of the new parent
         * @param {Function} onfinish - [optional] callback function
         */
        copy : function(newParentId, callback)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.newParentId = newParentId;

            Ajax.post('ajax_site_copy', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }

                Site.fireEvent( 'copy', [ Site, newParentId ] );

            }, params);
        },

        /**
         * Create a link into the parent to the site
         *
         * @param {Integer} newParentId - ID of the parent
         * @param {Function} onfinish - [optional] callback function
         */
        linked : function(newParentId, callback)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.newParentId = newParentId;

            Ajax.post('ajax_site_linked', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }

                Site.fireEvent( 'linked', [ Site, newParentId ] );

            }, params);
        },

        /**
         * Create a child site
         *
         * @method classes/projects/project/Site#createChild
         *
         * @param {String|Object} newname - String = new name of the child, Object = { name:'', title:'' }
         * @param {Function} onfinish - [optional] callback function
         * @param {Function} onrror   - [optional] function, that is triggered if an error occurred
         */
        createChild : function(newname, onfinish, onerror)
        {
            if ( typeof newname === 'undefined' ) {
                return;
            }

            var Site   = this,
                params = this.ajaxParams();

            if ( typeOf( newname ) == 'object' )
            {
                params.attributes = JSON.encode( newname );

            } else
            {
                params.attributes = JSON.encode({
                    name : newname
                });
            }


            if ( typeof onerror !== 'undefined' )
            {
                params.showError = false;
                params.onError   = onerror;
            }

            Ajax.post('ajax_site_children_create', function(result, Request)
            {
                if ( !result ) {
                    return;
                }

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                Site.fireEvent( 'createChild', [ Site, result.id ] );

            }, params);
        },

        /**
         * Is the Site active?
         *
         * @method classes/projects/project/Site#getAttribute
         * @return {Bool}
         */
        isActive : function()
        {
            return this.getAttribute( 'active' );
        },

        /**
         * Site attributes
         */

        /**
         * Get an site attribute
         *
         * @method classes/projects/project/Site#getAttribute
         * @param {String} k - Attribute name
         * @return {unknown_type}
         */
        getAttribute : function(k)
        {
            var attributes = this.options.attributes;

            if ( typeof attributes[ k ] !== 'undefined' ) {
                return attributes[ k ];
            }

            var oid = Slick.uidOf( this );

            if ( typeof window.$quistorage[ oid ] === 'undefined' ) {
                return false;
            }

            if ( typeof window.$quistorage[ oid ][ k ] !== 'undefined' ) {
                return window.$quistorage[ oid ][ k ];
            }

            return false;
        },

        /**
         * Get all attributes from the Site
         *
         * @method classes/projects/project/Site#getAttributes
         * @return {Object} Site attributes
         */
        getAttributes : function()
        {
            return this.options.attributes;
        },

        /**
         * Set an site attribute
         *
         * @method classes/projects/project/Site#setAttribute
         *
         * @param {String} k        - Name of the Attribute
         * @param {unknown_type} v - Value of the Attribute
         */
        setAttribute : function(k, v)
        {
            this.options.attributes[ k ] = v;
        },

        /**
         * If you want to set more than one attribute
         *
         * @method classes/projects/project/Site#setAttributes
         *
         * @param {Object} attributes - Object with attributes
         * @return {this}
         *
         * @example
         * Site.setAttributes({
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
         * Returns the needle request (Ajax) params
         *
         * @method classes/projects/project/Site#ajaxParams
         * @return {Object}
         */
        ajaxParams : function()
        {
            return {
                project : this.getProject().getName(),
                lang    : this.getProject().getLang(),
                id      : this.getId()
            };
        }
    });
});
