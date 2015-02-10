
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

define('classes/projects/project/Site', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function(QUI, DOM, Ajax)
{
    "use strict";

    /**
     * @class classes/projects/project/Site
     *
     * @param {Object} Project - classes/projects/Project
     * @param {Number} id - Site ID
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
            this.$loaded       = false;

            this.$workingId = 'site-'+
                              Project.getName() +'-'+
                              Project.getLang() +'-'+
                              id;

            this.parent({
                id : id
            });
        },

        /**
         * Load the site
         * Get all attributes from the DB
         *
         * @method classes/projects/project/Site#load
         * @param {Function} [onfinish] - (optional) callback Function
         * @return {Object} this (classes/projects/project/Site)
         */
        load : function(onfinish)
        {
            var params = this.ajaxParams(),
                Site   = this;

            Ajax.get('ajax_site_get', function(result, Request)
            {
                Site.setAttributes( result.attributes );
                Site.clearWorkingStorage();

                Site.$has_children = result.has_children || false;
                Site.$parentid     = result.parentid || false;
                Site.$url          = result.url || '';
                Site.$loaded       = true;

                Site.fireEvent( 'load', [ Site ] );

                if ( typeof onfinish === 'function' ) {
                    onfinish( Site, Request );
                }
            }, params);

            return this;
        },

        /**
         * Get the site ID
         *
         * @method classes/projects/project/Site#getId
         * @return {Number}
         */
        getId : function()
        {
            return this.getAttribute( 'id' );
        },

        /**
         * Get the site project
         *
         * @method classes/projects/project/Site#getProject
         * @return {Object} classes/projects/Project
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Return the rewrited url
         *
         * @return {String}
         */
        getUrl : function()
        {
            if ( typeof this.$url !== 'undefined' ) {
                return this.$url;
            }

            return '';
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
         * @return {Number}
         */
        countChild : function()
        {
            return ( this.$has_children ).toInt();
        },

        /**
         * Get the children
         *
         * @method classes/projects/project/Site#getChildren
         * @param {Function} [onfinish] - (optional), callback function
         * @param {Object} [params] - (optional)
         * @returns {Object} this (classes/projects/project/Site)
         */
        getChildren : function(onfinish, params)
        {
            var data = this.ajaxParams(),
                Site = this;

            data.params = JSON.encode( params || {} );

            Ajax.get('ajax_site_getchildren', function(result)
            {
                var children = result.children;

                if ( typeof onfinish === 'function' ) {
                    onfinish( children );
                }

                Site.fireEvent( 'getChildren', [ Site, children ] );

            }, data);

            return this;
        },

        /**
         * Return the parent
         *
         * @method classes/projects/project/Site#getParent
         * @return {Object|Boolean} classes/projects/project/Site | false
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
         * @param {Function} [onfinish] - (optional), callback function
         * @return {Object} this (classes/projects/project/Site)
         */
        activate : function(onfinish)
        {
            var Site = this;

            Ajax.post('ajax_site_activate', function(result)
            {
                if ( result )
                {
                    Site.setAttribute( 'active', 1 );
                    Site.clearWorkingStorage();
                }

                if ( typeof onfinish === 'function' ) {
                    onfinish( result );
                }

                if ( result ) {
                    Site.fireEvent( 'activate', [ Site ] );
                }

            }, this.ajaxParams());

            return this;
        },

        /**
         * Deactivate the site
         *
         * @method classes/projects/project/Site#deactivate
         * @fires deactivate
         * @param {Function} [onfinish] - (optional), callback function
         * @return {Object} this (classes/projects/project/Site)
         */
        deactivate : function(onfinish)
        {
            var Site = this;

            Ajax.post('ajax_site_deactivate', function(result)
            {
                if ( result === 0 )
                {
                    Site.setAttribute( 'active', 0 );
                    Site.clearWorkingStorage();
                }

                if ( typeof onfinish === 'function' ) {
                    onfinish( result );
                }

                if ( result === 0 ) {
                    Site.fireEvent( 'deactivate', [ Site ] );
                }

            }, this.ajaxParams());

            return this;
        },

        /**
         * Save the site
         *
         * @method classes/projects/project/Site#save
         * @fires save
         * @param {Function} [onfinish] - (optional), callback function
         * @return {Object} this (classes/projects/project/Site)
         */
        save : function(onfinish)
        {
            var Site   = this,
                params = this.ajaxParams(),
                status = this.getAttribute( 'active' );

            params.attributes = JSON.encode( this.getAttributes() );

            Ajax.post('ajax_site_save', function(result)
            {
                if ( result && result.attributes ) {
                    Site.setAttributes( result.attributes );
                }

                Site.$has_children = result && result.has_children || false;
                Site.$parentid     = result && result.parentid || false;
                Site.$url          = result && result.url || '';

                Site.clearWorkingStorage();

                // if status change, trigger events
                if ( Site.getAttribute( 'active' ) != status )
                {
                    if ( Site.getAttribute( 'active' ) == 1 )
                    {
                        Site.fireEvent( 'activate', [ Site ] );
                    } else
                    {
                        Site.fireEvent( 'deactivate', [ Site ] );
                    }
                }

                if ( typeof onfinish === 'function' ) {
                    onfinish( result );
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
         * @param {Function} [onfinish] - (optional), callback function
         */
        del : function(onfinish)
        {
            var Site = this;

            Ajax.post('ajax_site_delete', function(result)
            {
                if ( typeof onfinish === 'function' ) {
                    onfinish( result );
                }

                Site.fireEvent( 'delete', [ Site ] );

            }, this.ajaxParams());
        },

        /**
         * Move the site to another parent site
         *
         * @param {Number} newParentId - ID of the new parent
         * @param {Function} [callback] - (optional), callback function
         */
        move : function(newParentId, callback)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.newParentId = newParentId;

            Ajax.post('ajax_site_move', function(result)
            {
                if ( typeof callback === 'function' ) {
                    callback( result );
                }

                Site.fireEvent( 'move', [ Site, newParentId ] );

            }, params);
        },


        /**
         * Copy the site to another parent site
         *
         * @param {Number} newParentId - ID of the new parent
         * @param {Function} [callback] - (optional) callback function
         */
        copy : function(newParentId, callback)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.newParentId = newParentId;

            Ajax.post('ajax_site_copy', function(result)
            {
                if ( typeof callback === 'function' ) {
                    callback( result );
                }

                Site.fireEvent( 'copy', [ Site, newParentId ] );

            }, params);
        },

        /**
         * Create a link into the parent to the site
         *
         * @param {Number} newParentId - ID of the parent
         * @param {Function} [callback] - (optional) callback function
         */
        linked : function(newParentId, callback)
        {
            var Site   = this,
                params = this.ajaxParams();

            params.newParentId = newParentId;

            Ajax.post('ajax_site_linked', function(result)
            {
                if ( typeof callback === 'function' ) {
                    callback( result );
                }

                Site.fireEvent( 'linked', [ Site, newParentId ] );

            }, params);
        },

        /**
         * lock the site
         *
         * @param {function} callback
         */
        lock : function(callback)
        {
            Ajax.post('ajax_site_lock', function()
            {
                if ( typeof callback === 'function' ) {
                    callback();
                }
            }, this.ajaxParams());
        },

        /**
         * unlock the site
         *
         * @param {function} callback
         */
        unlock : function( callback )
        {
            Ajax.post('ajax_site_unlock', function()
            {
                if ( typeof callback === 'function' ) {
                    callback();
                }

            }, this.ajaxParams());
        },

        /**
         * Create a child site
         *
         * @method classes/projects/project/Site#createChild
         *
         * @param {String|Object} newname - String = new name of the child, Object = { name:'', title:'' }
         * @param {Function} [onfinish] - (optional) callback function
         * @param {Function} [onerror]   - (optional) function, that is triggered if an error occurred
         */
        createChild : function(newname, onfinish, onerror)
        {
            if ( typeof newname === 'undefined' ) {
                return;
            }

            var params = this.ajaxParams();

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

            var Site = this;

            Ajax.post('ajax_site_children_create', function(result)
            {
                if ( !result ) {
                    return;
                }

                if ( typeof onfinish === 'function' ) {
                    onfinish( result );
                }

                Site.fireEvent( 'createChild', [ Site, result.id ] );

            }, params);
        },

        /**
         * Is the Site active?
         *
         * @method classes/projects/project/Site#getAttribute
         * @return {Boolean}
         */
        isActive : function()
        {
            return this.getAttribute( 'active' );
        },

        /**
         * Working data
         */

        /**
         * clears the working storage for the site
         */
        clearWorkingStorage : function()
        {
            QUI.Storage.remove( this.getWorkingStorageId() );
        },

        /**
         * return the working storage id of the site
         *
         * @return {string}
         */
        getWorkingStorageId : function()
        {
            return this.$workingId;
        },

        /**
         * Has the site an working storage?
         *
         * @return {boolean}
         */
        hasWorkingStorage : function()
        {
            return QUI.Storage.get( this.getWorkingStorageId() ) ? true : false;
        },

        /**
         * Return the data of the working storage
         *
         * @returns {object|null|boolean}
         */
        getWorkingStorage : function()
        {
            var storage = QUI.Storage.get( this.getWorkingStorageId() );

            if ( !storage ) {
                return false;
            }

            return JSON.decode( storage );
        },

        /**
         * Set the working storage data to the site
         */
        restoreWorkingStorage : function()
        {
            var data = this.getWorkingStorage();

            if ( data ) {
                this.options.attributes = data;
            }
        },

        /**
         * Site attributes
         */

        /**
         * Get an site attribute
         *
         * @method classes/projects/project/Site#getAttribute
         * @param {String} k - Attribute name
         * @return {Boolean|Function|Number|String|Object}
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
         * @param {Boolean|Number|Function|Object} v - Value of the Attribute
         */
        setAttribute : function(k, v)
        {
            this.options.attributes[ k ] = v;

            if ( this.$loaded === false ) {
                return;
            }

            // locale storage
            QUI.Storage.set(
                this.getWorkingStorageId(),
                JSON.encode( this.options.attributes )
            );
        },

        /**
         * If you want to set more than one attribute
         *
         * @method classes/projects/project/Site#setAttributes
         *
         * @param {Object} attributes - Object with attributes
         * @return {Object} this (classes/projects/project/Site)
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

            for ( var k in attributes )
            {
                if ( attributes.hasOwnProperty( k ) ) {
                    this.setAttribute( k, attributes[ k ] );
                }
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
                project : this.getProject().encode(),
                id      : this.getId()
            };
        }
    });
});
