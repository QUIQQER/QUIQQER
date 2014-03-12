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

    'qui/controls/windows/Confirm',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'controls/projects/TypeSitemap'

], function(QUIConfirm, QUI_Item, QUI_SitemapItem, QUI_TypeSitemap)
{
    "use strict";

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
    return new Class({

        Extends : QUIConfirm,
        Type    : 'controls/projects/TypeWindow',

        Binds : [
            '$onCreate'
        ],

        options : {
            multible : false,
            project  : false,

            title     : 'Seitentypen Auswahl',
            icon      : 'icon-magic',
            maxHeight : 500,
            maxWidth  : 400,
            message   : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Sitemap = null;
            this.$Elm     = null;

            this.addEvents({
                'onOpen' : this.$onOpen
            });
        },

        /**
         * Create the Window with a type sitemap
         *
         * @method QUI.controls.projects.TypeWindow#create
         * @return {DOMNode}
         */
        $onOpen : function()
        {
            this.Loader.show();

            var Content = this.getContent();
                Content.set( 'html', '' );

            if ( this.getAttribute( 'message')  )
            {
                new Element('div', {
                    html : this.getAttribute( 'message' )
                }).inject( Content );
            }

            var self        = this,
                SitemapBody = new Element( 'div' ).inject( Content );

            require(['controls/projects/TypeSitemap'], function(TyeSitemap)
            {
                self.$Sitemap = new TyeSitemap(SitemapBody, {
                    project  : self.getAttribute( 'project' ),
                    multible : self.getAttribute( 'multible' )
                }).inject( SitemapBody );

                self.$Sitemap.open();

                self.Loader.hide();
            });
        },

        /**
         * submit the window
         */
        submit : function()
        {
            var values = [];

            if ( this.$Sitemap ) {
                values = this.$Sitemap.getValues();
            }

            this.fireEvent( 'submit', [ this, values ] );

            if ( this.getAttribute( 'autoclose' ) ) {
                this.close();
            }
        }
    });
});