/**
 * A project Site Object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/contextmenu/Menu
 * @requires controls/contextmenu/Item
 *
 * @module classes/projects/Site
 * @package com.pcsg.qui.js.classes.project
 * @namespace QUI.classes.project
 *
 * @event onLoad [ this ]
 * @event onGetChildren [ this, {Array} ]
 * @event onActivate [ this ]
 * @event onDeactivate [ this ]
 * @event onDelete [ this ]
 * @event createChild [ this ]
 */

define('classes/projects/project/Site', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function(QUI, DOM, Ajax)
{
    "use strict";

    /**
     * @class QUI.classes.projects.Site
     *
     * @param {QUI.classes.projects.Project} Project
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
         * @method QUI.classes.projects.Site#load
         * @param {Function} onfinish - [optional] callback Function
         * @return {this} self
         */
        load : function(onfinish)
        {
            var params = this.ajaxParams(),
                Site   = this;

            params.onfinish = onfinish;

            Ajax.get('ajax_site_get', function(result, Request)
            {
                Site.setAttributes( result.attributes );
                Site.$has_children = result.has_children || false;
                Site.$parentid     = result.parentid || false;

                Site.fireEvent( 'load', [ Site ] );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( Site, Request );
                }
            }, params);

            return this;
        },

        /**
         * Get the site ID
         *
         * @method QUI.classes.projects.Site#getId
         * @return {Integer}
         */
        getId : function()
        {
            return this.getAttribute( 'id' );
        },

        /**
         * Get the site project
         *
         * @method QUI.classes.projects.Site#getProject
         * @return {QUI.classes.projects.Project}
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
         * Get the children
         *
         * @method QUI.classes.projects.Site#getChildren
         * @param {Function} onfinish - [optional] callback function
         * @returns {this}
         */
        getChildren : function(onfinish)
        {
            var params = this.ajaxParams(),
                Site   = this;

            params.onfinish = onfinish;

            Ajax.get('ajax_site_getchildren', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'getChildren', [ Site, result ] );

            }, params);

            return this;
        },

        /**
         * Return the parent
         *
         * @method QUI.classes.projects.Site#getParent
         * @return {QUI.classes.projects.Site|false}
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
         * @method QUI.classes.projects.Site#ajaxParams
         * @fires activate
         * @param {Function} onfinish - [optional] callback function
         * @return {this}
         */
        activate : function(onfinish)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.onfinish = onfinish || false;

            Ajax.post('ajax_site_activate', function(result, Request)
            {
                Site.setAttribute( 'active', 1 );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'activate', [ Site ] );

            }, params);

            return this;
        },

        /**
         * Deactivate the site
         *
         * @method QUI.classes.projects.Site#deactivate
         * @fires deactivate
         * @param {Function} onfinish - [optional] callback function
         * @return {this}
         */
        deactivate : function(onfinish)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.onfinish = onfinish || false;

            Ajax.post('ajax_site_deactivate', function(result, Request)
            {
                Site.setAttribute( 'active', 0 );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'deactivate', [ Site ] );
            }, params);

            return this;
        },

        /**
         * Save the site
         *
         * @method QUI.classes.projects.Site#save
         * @fires save
         * @param {Function} onfinish - [optional] callback function
         * @return {this}
         */
        save : function(onfinish)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.onfinish   = onfinish;
            params.attributes = JSON.encode( this.getAttributes() );

            Ajax.post('ajax_site_save', function(result, Request)
            {
                Site.setAttributes( result.attributes );
                Site.$has_children = result.has_children || false;
                Site.$parentid     = result.parentid || false;

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'save', [ Site ] );
            }, params);

            return this;
        },

        /**
         * Delete the site
         * Delete it in the Database, too
         *
         * @method QUI.classes.projects.Site#del
         * @param {Function} onfinish - [optional] callback function
         */
        del : function(onfinish)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.onfinish   = onfinish;

            Ajax.post('ajax_site_delete', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'delete', [ Site ] );
            }, params);
        },

        /**
         * Create a child site
         *
         * @method QUI.classes.projects.Site#createChild
         *
         * @param {String} newname    - new name of the child
         * @param {Function} onfinish - [optional] callback function
         */
        createChild : function(newname, onfinish)
        {
            if ( typeof newname === 'undefined' ) {
                return;
            }

            var params = this.ajaxParams();

            params.onfinish   = onfinish || false;
            params.attributes = JSON.encode({
                name : newname
            });

            Ajax.post('ajax_site_children_create', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Site = Request.getAttribute( 'Site' );

                Site.fireEvent( 'createChild', [ Site, result.id ] );

            }, params);
        },

        /**
         * Is the Site active?
         *
         * @method QUI.classes.projects.Site#getAttribute
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
         * @method QUI.classes.projects.Site#getAttribute
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
         * @method QUI.classes.projects.Site#getAttributes
         * @return {Object} Site attributes
         */
        getAttributes : function()
        {
            return this.options.attributes;
        },

        /**
         * Set an site attribute
         *
         * @method QUI.classes.projects.Site#setAttribute
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
         * @method QUI.classes.projects.Site#setAttributes
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
         * @method QUI.classes.projects.Site#ajaxParams
         * @return {Object}
         */
        ajaxParams : function()
        {
            return {
                project : this.getProject().getName(),
                lang    : this.getProject().getLang(),
                id      : this.getId(),
                Site    : this
            };
        }
    });
});