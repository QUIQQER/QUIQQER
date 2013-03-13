/**
 * A project Site Object
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/contextmenu/Menu
 * @requires controls/contextmenu/Item
 *
 * @module classes/project/Site
 * @package com.pcsg.qui.js.classes.project
 * @namespace QUI.classes.project
 *
 * @event onRefresh [ this ]
 */

define('classes/project/Site', [

    'classes/DOM',

    'css!classes/project/Site.css'

], function(DOM)
{
    QUI.namespace( 'classes.project' );

    /**
     * @class QUI.classes.project.Site
     *
     * @param {QUI.classes.project.Project} Project
     * @param {Integer} id - Site ID
     *
     * @fires onStatusEditBegin - this
     * @fires onStatusEditEnd   - this
     */
    QUI.classes.project.Site = new Class({

        Implements: [ DOM ],

        options : {
            Project    : '',
            id         : 0,
            attributes : {}
        },

        initialize : function(Project, id)
        {
            this.init({
                Project    : Project,
                id         : id,
                attributes : {}
            });
        },

        /**
         * Load the site
         * Get all attributes from the DB
         *
         * @method QUI.classes.project.Site#load
         *
         * @param {Function} onfinish      - callback Function
         * @param {Object} params        - callback Object
         */
        load : function(onfinish, params)
        {
            params = params || {};

            params.project  = this.getProject().getAttribute('name');
            params.lang     = this.getProject().getAttribute('lang');
            params.id       = this.getId();
            params.Site     = this;
            params.onfinish = onfinish;

            QUI.Ajax.get('ajax_site_get', function(result, Ajax)
            {
                var Site = Ajax.getAttribute('Site');
                Site.setAttributes( result );

                Site.fireEvent( 'refresh', [ Site ] );

                if ( Ajax.getAttribute( 'onfinish' ) ) {
                    Ajax.getAttribute( 'onfinish' )( Site, Ajax );
                }
            }, params);
        },

        /**
         * Get the site ID
         *
         * @method QUI.classes.project.Site#getId
         * @return {Integer}
         */
        getId : function()
        {
            return this.getAttribute('id');
        },

        /**
         * Get the site project
         *
         * @method QUI.classes.project.Site#getProject
         * @return {QUI.classes.project.Project}
         */
        getProject : function()
        {
            return this.getAttribute('Project');
        },

        /**
         * Returns the needle request (Ajax) params
         *
         * @method QUI.classes.project.Site#ajaxParams
         * @return {Object}
         */
        ajaxParams : function()
        {
            return {
                project : this.getProject().getAttribute('name'),
                lang    : this.getProject().getAttribute('lang'),
                id      : this.getId(),
                Site    : this
            };
        },

        /**
         * Activate the site
         *
         * @method QUI.classes.project.Site#ajaxParams
         * @return {this}
         */
        activate : function()
        {
            this.fireEvent('onStatusEditBegin', [this]);
            this.getProject().fireEvent('onSiteStatusEditBegin', [this]);

            QUI.lib.Sites.activate(function(result, Ajax)
            {
                if (result) {
                    this.setAttribute('active', 1);
                }

                this.fireEvent('onStatusEditEnd', [this]);
                this.getProject().fireEvent('onSiteStatusEditEnd', [this]);
            }.bind(this), this.ajaxParams());

            return this;
        },

        /**
         * Deactivate the site
         *
         * @method QUI.classes.project.Site#deactivate
         * @return {this}
         */
        deactivate : function()
        {
            this.fireEvent('onStatusEditBegin', [this]);
            this.getProject().fireEvent('onSiteStatusEditBegin', [this]);

            QUI.lib.Sites.deactivate(function(result, Ajax)
            {
                if (result) {
                    this.setAttribute('active', 0);
                }

                this.fireEvent('onStatusEditEnd', [this]);
                this.getProject().fireEvent('onSiteStatusEditEnd', [this]);
            }.bind(this), this.ajaxParams());

            return this;
        },

        /**
         * Save the site
         *
         * @method QUI.classes.project.Site#save
         *
         * @return {this}
         */
        save : function()
        {
            this.fireEvent('onStatusEditBegin', [this]);
            this.getProject().fireEvent('onSiteStatusEditBegin', [this]);

            QUI.lib.Sites.save(
                function(result, Ajax)
                {
                    this.fireEvent('onStatusEditEnd', [this]);
                    this.getProject().fireEvent('onSiteStatusEditEnd', [this]);

                }.bind(this),
                this.ajaxParams(),
                this.getAttributes()
            );

            return this;
        },

        /**
         * Delete the site
         *
         * @method QUI.classes.project.Site#del
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
                Site.fireEvent('delete', [Site]);

            }, this.ajaxParams());
        },

        /**
         * Create a child site
         *
         * @method QUI.classes.project.Site#createChild
         *
         * @param {String} newname - [optional, if no newname was passed,
         *         a window would be open]
         */
        createChild : function(newname)
        {
            if (typeof newname === 'undefined')
            {
                QUI.Windows.create('prompt', {
                    title  : 'Wie soll die neue Seite heißen?',
                    text   : 'Bitte geben Sie ein Namen für die neue Seite an',
                    texticon    : URL_BIN_DIR +'48x48/filenew.png',
                    information : 'Sie legen eine neue Seite unter '+ this.getAttribute('name') +'.html an.',
                    Site   : this,
                    events :
                    {
                        onSubmit : function(result, Win) {
                            Win.getAttribute('Site').createChild( result );
                        }
                    }
                });

                return;
            }

            QUI.lib.Sites.createChild(
                function(result, Request)
                {
                    // open the site in the sitemap
                    var i, len, Panel, items;

                    var Site   = Request.getAttribute('Site'),
                        id     = Site.getId(),
                        panels = QUI.lib.Sites.getProjectPanels( Site ),

                        func_close = function(Item) {
                            Item.close();
                        };

                    for (i = 0, len = panels.length; i < len; i++)
                    {
                        Panel = panels[i];

                        // if site is inb the map, it must be refreshed
                        items = Panel.getSitemapItemsById( id );

                        if (items.length) {
                            items.each( func_close );
                        }

                        panels[i].openSite( result.id );
                    }
                },
                this.ajaxParams(),
                {
                    name : newname,
                    title : newname
                }
            );
        },

        /**
         * Get an site attribute
         *
         * @method QUI.classes.project.Site#getAttribute
         *
         * @param {String} k - Attribute name
         * @return {unknown_type}
         */
        getAttribute : function(k)
        {
            var attributes = this.options.attributes;

            if (typeof attributes[ k ] !== 'undefined') {
                return attributes[ k ];
            }

            var oid = Slick.uidOf(this);

            if (typeof QUI.$storage[ oid ] === 'undefined') {
                return false;
            }

            if (typeof QUI.$storage[ oid ][k] !== 'undefined') {
                return QUI.$storage[ oid ][k];
            }

            return false;
        },

        /**
         * Get all attributes from the Site
         *
         * @method QUI.classes.project.Site#getAttributes
         *
         * @return {Object}
         */
        getAttributes : function()
        {
            return this.options.attributes;
        },

        /**
         * Set an site attribute
         *
         * @method QUI.classes.project.Site#setAttribute
         *
         * @param {String} k        - Name of the Attribute
         * @param {unknown_type} v - Value of the Attribute
         */
        setAttribute : function(k, v)
        {
            this.options.attributes[k] = v;
        },

        /**
         * If you want to set more than one attribute
         *
         * @method QUI.classes.project.Site#setAttributes
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

            for (var k in attributes) {
                this.setAttribute(k, attributes[k]);
            }

            return this;
        }
    });

    return QUI.classes.project.Site;
});