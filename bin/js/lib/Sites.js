/**
 * Methods for Sites
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module lib/Sites
 * @package com.pcsg.qui.js
 * @namespace QUI.lib
 */

define('lib/Sites', [

    'lib/Controls'

], function()
{
    "use strict";

    QUI.namespace('lib');

    QUI.lib.Sites =
    {
        /**
         * Get all Children from a Site
         *
         * @method QUI.lib.Sites#getChildren
         *
         * @param {Function} onfinish     - Callback
         * @param {Object} params        - Site parameter
         */
        /*
        getChildren : function(onfinish, params)
        {
            params = params || {};
            params.onfinish = onfinish;

            if (this.checkAjaxSiteParams(params) === false) {
                return false;
            }

            QUI.Ajax.get('ajax_site_getchildren', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);
        },
        */
        /**
         * Activate a Site
         *
         * @method QUI.lib.Sites#activate
         *
         * @param {Function} onfinish     - Callback
         * @param {Object} params        - Site parameter
         * @param {Bool}
         */
        activate : function(onfinish, params)
        {
            params = params || {};
            params.onfinish = onfinish;

            if (this.checkAjaxSiteParams(params) === false) {
                return false;
            }

            QUI.Ajax.post('ajax_site_activate', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);

            return true;
        },

        /**
         * Deactivate a Site
         *
         * @method QUI.lib.Sites#deactivate
         *
         * @param {Function} onfinish - Callback
         * @param {Object} params      - Site parameter
         * @return {Bool}
         */
        deactivate : function(onfinish, params)
        {
            params = params || {};
            params.onfinish = onfinish;

            if (this.checkAjaxSiteParams(params) === false) {
                return false;
            }

            QUI.Ajax.post('ajax_site_deactivate', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);

            return true;
        },

        /**
         * Saves a site with new attributes
         *
         * @method QUI.lib.Sites#save
         *
         * @param {Function} onfinish - Callback
         * @param {Object} params      - Site parameter
         * @param {Object} attributes - Site attributes
         * @return {Bool}
         */
        save : function(onfinish, params, attributes)
        {
            params     = params || {};
            attributes = QUI.Utils.filterForJSON( attributes );

            if (attributes.project) {
                delete attributes.project;
            }

            if (attributes.lang) {
                delete attributes.lang;
            }

            if (attributes.id) {
                delete attributes.id;
            }

            params.onfinish   = onfinish;
            params.attributes = JSON.encode( attributes );

            if (this.checkAjaxSiteParams(params) === false) {
                return false;
            }

            QUI.Ajax.post('ajax_site_save', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);

            return true;
        },

        /**
         * Delete a site
         *
         * @method QUI.lib.Sites#del
         *
         * @param {Function} onfinish - Callback
         * @param {Object} params      - Site parameter
         * @return {Bool}
         */
        del : function(onfinish, params)
        {
            params = params || {};
            params.onfinish = onfinish;

            if (this.checkAjaxSiteParams(params) === false) {
                return false;
            }

            QUI.Ajax.post('ajax_site_delete', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);

            return true;
        },

        /**
         * Create a new Site
         *
         * @method QUI.lib.Sites#createChildren
         *
         * @param {Function} onfinish       - Callback function
         * @param {Object} parent_params  - Parent Site Parameter
         * @param {Object} params           - parameter
         * @return {Bool}
         */
        createChild : function(onfinish, parent_params, params)
        {
            parent_params = parent_params || {};

            parent_params.onfinish   = onfinish;
            parent_params.attributes = JSON.encode( params );

            if (this.checkAjaxSiteParams( parent_params ) === false) {
                return false;
            }

            QUI.Ajax.post('ajax_site_children_create', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, parent_params);

            return true;
        },

        /**
         * Checks, if all needle site params are given
         * If not all needles are given, the QUI.MH.addError are exec
         *
         * @method QUI.lib.Sites#checkAjaxSiteParams
         *
         * @param {Object} params
         * @return {Bool}
         */
        checkAjaxSiteParams : function(params)
        {
            if (!params.project)
            {
                QUI.MH.addError('Projekt ist nicht definiert');
                return false;
            }

            if (!params.lang)
            {
                QUI.MH.addError('Projektsprache ist nicht definiert');
                return false;
            }

            if (!params.id)
            {
                QUI.MH.addError('Seiten ID ist nicht definiert');
                return false;
            }

            return true;
        },

        /**
         * Get all project panels fit for the site
         * I.e. the panel must have the same project like the site
         *
         * @method QUI.lib.Sites#getProjectsPanels
         *
         * @param {QUI.classes.projects.Site} Site
         * @return {Array}
         */
        getProjectPanels : function(Site)
        {
            var i, len;

            var panels  = QUI.Controls.get('projects-panel'),

                Project = Site.getProject(),
                lang    = Project.getAttribute('lang'),
                project = Project.getAttribute('name'),
                id      = Site.getId(),

                result  = [];

            for ( i = 0, len = panels.length; i < len; i++ )
            {
                if ( panels[i].getAttribute('project') != project ||
                     panels[i].getAttribute('lang') != lang )
                {
                    return;
                }

                result.push( panels[i] );
            }

            return result;
        },

        /**
         * Get all site panels fit for the site
         * I.e. the panel must have the same site
         *
         * @method QUI.lib.Sites#getSitePanels
         *
         * @param {QUI.classes.projects.Site} Site
         * @return {Array}
         */
        getSitePanels : function(Site)
        {
            return QUI.Controls.get('projects-site-'+ Site.getId() +'-panel');
        },

        /**
         * Opens a Site Sitemap Item
         *
         * @method QUI.lib.Sites#onopen
         * @param {QUI.controls.sitemap.Item} Parent
         */
        onopen : function(Parent)
        {
            Parent.clearChildren();
            Parent.setAttribute('ricon', Parent.getAttribute('icon'));

            Asset.image(URL_BIN_DIR +'images/loader.gif',
            {
                onLoad : function()
                {
                    this.setAttribute('icon', URL_BIN_DIR +'images/loader.gif');

                    var Project = this.getAttribute('Project');

                    QUI.lib.Sites.getChildren(function(result, Ajax)
                    {
                        var i, len, Child;

                        var Parent  = Ajax.getAttribute('Parent'),
                            Project = Parent.getAttribute('Project');

                        for ( i = 0, len = result.length; i < len; i++ )
                        {
                            Child = QUI.lib.Sites.parseArrayToSitemapitem( result[i] );

                            Child.setAttribute('Project', Project);
                            Child.addEvent('onOpen', QUI.lib.Sites.onopen);

                            Parent.appendChild( Child );
                        }

                        Parent.setAttribute('icon', Parent.getAttribute('ricon'));

                    }, {
                        project : Project.getAttribute('name'),
                        lang    : Project.getAttribute('lang'),
                        id      : this.getAttribute('value'),
                        Parent  : this
                    });

                }.bind( Parent )
            });
        },

        /**
         * Parse an Site result from Ajax to an {QUI.controls.sitemap.Item}
         *
         * @method QUI.lib.Sites#parseArrayToSitemapitem
         * @return {QUI.controls.sitemap.Item}
         */
        parseArrayToSitemapitem : function(result)
        {
            var Itm = new QUI.controls.sitemap.Item({
                name  : result.name,
                index : result.id,
                value : result.id,
                text  : result.title,
                alt   : result.name +'.html',
                icon  : URL_BIN_DIR +'16x16/page_white.png',
                hasChildren : (result.has_children).toInt()
            });

            /*
            if (typeof result.events !== 'undefined')
            {
                Itm.addEvents( result.events );
            } else
            {
                Itm.addEvents({
                    onClick : function(Itm)
                    {
                        var Project = Itm.getAttribute('Project');

                        QUI.Projects.openSiteInPanel(
                            Project.getAttribute('name'),
                            Project.getAttribute('lang'),
                            Itm.getAttribute('value')
                        );
                    },
                    onContextMenu : function(Item, event)
                    {
                        Item.getContextMenu()
                            .setTitle( Item.getAttribute('text') +' - '+ Item.getAttribute('value') )
                            .setPosition( event.page.x, event.page.y )
                            .show();

                        event.stop();
                    }
                });
            }
            */

            if (result.nav_hide == '1') {
                Itm.addIcon( URL_BIN_DIR +'16x16/navigation_hidden.png' );
            }

            if (result.linked == '1')
            {
                Itm.setAttribute('linked', true);
                Itm.addIcon( URL_BIN_DIR +'16x16/linked.png' );
            }

            if (result.icon_16x16) {
                Itm.setAttribute('icon', result.icon_16x16);
            }

            // Activ / Inactive
            if (result.active === 0)
            {
                Itm.deactivate();
            } else
            {
                Itm.activate();
            }

            // contextmenu
            Itm.getContextMenu()
                .appendChild(
                    new QUI.controls.contextmenu.Item({
                        name   : 'site-copy-'+ Itm.getId(),
                        text   : 'kopieren',
                        icon   : URL_BIN_DIR +'16x16/copy.png',
                        events :
                        {
                            onClick : function(Item, event)
                            {
                                console.info(Item);
                            }
                        }
                    })
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name   : 'site-paste-'+ Itm.getId(),
                        text   : 'einfügen',
                        icon   : URL_BIN_DIR +'16x16/paste.png',
                        events :
                        {
                            onClick : function(Item, event)
                            {
                                console.info(Item);
                            }
                        }
                    })
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name   : 'site-cut-'+ Itm.getId(),
                        text   : 'ausschneiden',
                        icon   : URL_BIN_DIR +'16x16/cut.png',
                        events :
                        {
                            onClick : function(Item, event)
                            {
                                console.info(Item);
                            }
                        }
                    })
                );

            return Itm;
        },

        /**
         * PanelButton Methoden
         */
        PanelButton :
        {
            activate : function(Btn)
            {
                Btn.getAttribute('Panel')
                   .getAttribute('Site')
                   .activate();
            },

            deactivate : function(Btn)
            {
                Btn.getAttribute('Panel')
                   .getAttribute('Site')
                   .deactivate();
            },

            save : function(Btn)
            {
                Btn.getAttribute('Panel')
                   .getAttribute('Control')
                   .unload();

                Btn.getAttribute('Panel')
                   .getAttribute('Site')
                   .save();
            },

            createChild : function(Btn)
            {
                Btn.getAttribute('Panel')
                   .getAttribute('Site')
                   .createChild();
            },

            del : function(Btn)
            {
                Btn.getAttribute('Panel')
                   .getAttribute('Site')
                   .del();
            }
        }
    };

    return QUI.lib.Sites;
});
