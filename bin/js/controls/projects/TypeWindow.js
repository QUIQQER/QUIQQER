/**
 * The type window for the project
 *
 * The type window create a QUI.controls.windows.Submit
 * with all available types for the project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/sitemap/Map
 * @requires controls/sitemap/Item
 * @requires controls/projects/TypeSitemap
 *
 * @module controls/projects/TypeSitemap
 * @package com.pcsg.qui.js.controls.projects
 * @namespace QUI.controls.projects
 */

define('controls/projects/TypeWindow', [

    'controls/Control',
    'controls/sitemap/Map',
    'controls/sitemap/Item',
    'controls/projects/TypeSitemap'

], function(Control, QUI_Item, QUI_SitemapItem, QUI_TypeSitemap)
{
    "use strict";

    QUI.namespace('controls.projects');

    /**
     * @class QUI.controls.projects.TypeWindow
     *
     * @fires onSubmit [result, Win]
     * @fires onCancel [Win]
      *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.TypeWindow = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.projects.TypeWindow',

        options : {
            multible : false,
            project  : false,

            title   : 'Seitentypen Auswahl',
            icon    : URL_BIN_DIR +'16x16/types.png',
            height  : 400,
            width   : 350,
            message : false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Sitemap = null;
            this.$Elm     = null;
        },

        /**
         * Create the Window with a type sitemap
         *
         * @method QUI.controls.projects.TypeWindow#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new QUI.controls.windows.Submit({
                title  : this.getAttribute( 'title' ),
                icon   : this.getAttribute( 'icon' ),
                height : this.getAttribute( 'height' ),
                width  : this.getAttribute( 'width' ),
                information : '<div class="types-sitemap"></div>',

                Control : this,
                events  :
                {
                    onDrawEnd : function(Win, MuiWin)
                    {
                        var Control     = Win.getAttribute( 'Control' ),
                            Body        = Win.getBody(),
                            SitemapBody = Body.getElement( '.types-sitemap' ),
                            Text        = Body.getElement( '.text' ),
                            Information = Body.getElement( '.information' );

                        if ( Control.getAttribute( 'message')  )
                        {
                            new Element('div', {
                                html : Control.getAttribute( 'message' )
                            }).inject( SitemapBody, 'before' );
                        }

                        if ( Text ) {
                            Text.destroy();
                        }

                        require(['controls/projects/TypeSitemap'], function(Control)
                        {
                            this.$Sitemap = new Control(SitemapBody, {
                                project  : this.getAttribute( 'project' ),
                                multible : this.getAttribute( 'multible' )
                            });

                        }.bind( Control ));
                    },

                    onSubmit : function(Win)
                    {
                        var Control = Win.getAttribute( 'Control' ),
                            Sitemap = Control.$Sitemap;

                        if ( Sitemap )
                        {
                            Control.fireEvent('submit', [
                                Sitemap.getValues(),
                                Control
                            ]);
                        }
                    }
                }
            }).create();

            return this.$Elm;
        }
    });

    return QUI.controls.projects.TypeWindow;
});