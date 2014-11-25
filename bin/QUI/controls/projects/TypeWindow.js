
/**
 * The type window for the project
 *
 * The type window create a qui/controls/windows/Confirm
 * with all available types for the project
 *
 * @module controls/projects/TypeWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require controls/projects/TypeSitemap
 * @require Locale
 */

define('controls/projects/TypeWindow', [

    'qui/controls/windows/Confirm',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'controls/projects/TypeSitemap',
    'Locale'

], function(QUIConfirm, QUI_Item, QUI_SitemapItem, QUI_TypeSitemap, Locale)
{
    "use strict";

    /**
     * @class controls/projects/TypeWindow
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
            pluginsSelectable : false,

            title     : Locale.get( 'quiqqer/system', 'projects.typewindow.title' ),
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
         * @method controls/projects/TypeWindow#create
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
                self.$Sitemap = new TyeSitemap({
                    project  : self.getAttribute( 'project' ),
                    multible : self.getAttribute( 'multible' ),
                    pluginsSelectable : self.getAttribute( 'pluginsSelectable' )
                }).inject( SitemapBody );

                self.$Sitemap.open();

                self.Loader.hide();
            });
        },

        /**
         * submit the window
         *
         * @method controls/projects/TypeWindow#submit
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