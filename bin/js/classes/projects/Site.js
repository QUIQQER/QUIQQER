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

define('classes/projects/Site', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.projects' );

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
    QUI.classes.projects.Site = new Class({

        Extends : DOM,
        Type    : 'QUI.classes.projects.Site',

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

            this.init({
                id : id
            });
        },

        /**
         * Load the site
         * Get all attributes from the DB
         *
         * @method QUI.classes.projects.Site#load
         *
         * @param {Function} onfinish - [optional] callback Function
         * @return {this}
         */
        load : function(onfinish)
        {
            var params = this.ajaxParams(),
                Site   = this;

            params.onfinish = onfinish;

            QUI.Ajax.get('ajax_site_get', function(result, Request)
            {
                Site.setAttributes( result.attributes );
                Site.$has_children = result.has_children || false;

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

            QUI.Ajax.get('ajax_site_getchildren', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'getChildren', [ Site, result ] );

            }, params);

            return this;
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

            QUI.Ajax.post('ajax_site_activate', function(result, Request)
            {
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

            QUI.Ajax.post('ajax_site_deactivate', function(result, Request)
            {
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

            QUI.Ajax.post('ajax_site_save', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                Site.fireEvent( 'save', [ Site ] );
            }, params);

            return this;
        },

        /**
         * Delete the site
         *
         * @method QUI.classes.projects.Site#del
         *
         * @param {Bool} check - [optional if true, no aksing popup will be shown]
         */
        del : function(check)
        {
            if (typeof check === 'undefined')
            {
                QUI.Windows.create('submit', {
                    title  : 'Seite #'+ this.getId() +' löschen',
                    text   : 'Möchten Sie die Seite #'+ this.getId() +' '+ this.getAttribute('name') +'.html wirklich löschen?',
                    texticon    : URL_BIN_DIR +'48x48/trashcan_empty.png',
                    information :
                        'Die Seite wird in den Papierkorb gelegt und kann wieder hergestellt werden.' +
                        'Auch alle Unterseiten und Verknüpfungen werden in den Papierkorb gelegt.',
                    Site   : this,
                    height : 200,
                    events :
                    {
                        onSubmit : function(Win) {
                            Win.getAttribute('Site').del( true );
                        }
                    }
                });

                return;
            }

            QUI.lib.Sites.del(function(result, Request)
            {
                // open the site in the sitemap
                var i, len, items;

                var Site       = Request.getAttribute('Site'),
                    id         = Site.getId(),
                    panels     = QUI.lib.Sites.getProjectPanels( Site ),
                    sitepanels = QUI.lib.Sites.getSitePanels( Site ),

                    func_destroy = function(Item) {
                        Item.destroy();
                    };

                // destroy all sites with the id
                for (i = 0, len = panels.length; i < len; i++)
                {
                    items = panels[i].getSitemapItemsById( id );

                    if (items.length) {
                        items.each( func_destroy );
                    }
                }

                // destroy all panels with the site id
                sitepanels.each(function(Panel) {
                    Panel.close();
                });

                // fire the delete event
                Site.fireEvent( 'delete', [ Site ] );

            }, this.ajaxParams());
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

            QUI.Ajax.post('ajax_site_children_create', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Site = Request.getAttribute( 'Site' );

                Site.fireEvent( 'createChild', [ Site ] );

            }, params);
        },


        /**
         * Site attributes
         */

        /**
         * Get an site attribute
         *
         * @method QUI.classes.projects.Site#getAttribute
         *
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

            if ( typeof QUI.$storage[ oid ] === 'undefined' ) {
                return false;
            }

            if ( typeof QUI.$storage[ oid ][ k ] !== 'undefined' ) {
                return QUI.$storage[ oid ][ k ];
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

    return QUI.classes.projects.Site;
});