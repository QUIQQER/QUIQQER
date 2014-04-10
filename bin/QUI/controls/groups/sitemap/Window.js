/**
 * Groups Sitemapwindow
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/sitemap/Window
 * @class controls/groups/sitemap/Window
 *
 * @event onSubmit [ this, values ]
 */

define('controls/groups/sitemap/Window', [

    'qui/controls/windows/Confirm',
    'controls/groups/Sitemap'

], function(QUIConfirm, GroupSitemap)
{
    "use strict";

    /**
     * @class controls/groups/sitemap/Window
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIConfirm,
        Type    : 'controls/groups/sitemap/Window',

        Binds : [
            '$onWindowCreate',
            '$onSubmit'
        ],

        options : {
            multible : false,
            message  : false,
            title    : 'Gruppenauswahl',
            text     : 'Wählen Sie eine Gruppe aus',
            icon     : 'icon-group',
            maxHeight   : 600,
            maxWidth    : 450
        },

        initialize : function(options)
        {
            this.$Win = null;
            this.$Map = null;

            this.parent( options );
        },

        /**
         * event : onCreate
         */
        open : function()
        {
            this.parent();

            var Content    = this.getContent(),
                SubmitBody = Content.getElement( '.submit-body' );

            var SitemapBody = new Element('div', {
                'class' : 'group-sitemap'
            }).inject( Content );


            if ( this.getAttribute( 'message' ) )
            {
                new Element('div', {
                    html : this.getAttribute( 'message' )
                }).inject( Content, 'top' );
            }

            this.$Map = new GroupSitemap({
                multible : this.getAttribute( 'multible' )
            }).inject( SitemapBody );
        },

        /**
         * Submit the window
         *
         * @method qui/controls/windows/Confirm#submit
         */
        submit : function()
        {
            this.fireEvent( 'submit', [ this, this.$Map.getValues() ] );

            if ( this.getAttribute( 'autoclose' ) ) {
                this.close();
            }
        }
    });
});