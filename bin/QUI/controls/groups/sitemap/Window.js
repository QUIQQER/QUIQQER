/**
 * Groups Sitemapwindow
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @class controls/groups/sitemap/Window
 * @package com.pcsg.qui.js.controls.groups.sitemap.Window
 * @namespace QUI.controls.groups.sitemap
 *
 * @event onSubmit [ this, values ]
 */

define('controls/groups/sitemap/Window', [

    'qui/controls/Control',
    'controls/groups/Sitemap',
    'qui/controls/windows/Confirm'

], function(QUIControl, GroupSitemap, QUIConfirm)
{
    "use strict";

    /**
     * @class controls/groups/sitemap/Window
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/groups/sitemap/Window',

        Binds : [
            '$onWindowCreate',
            '$onSubmit'
        ],

        options : {
            multible : false,
            message  : false,
            title    : 'Gruppenauswahl',
            text     : 'Wählen Sie eine Gruppe aus'
        },

        initialize : function(options)
        {
            this.$Win = null;
            this.$Map = null;

            this.parent( options );
        },

        /**
         * Return the the Window
         *
         * @return {qui/controls/windows/Confirm}
         */
        create : function()
        {
            this.$Win = new QUIConfirm({
                title   : this.getAttribute( 'title' ),
                text    : this.getAttribute( 'text' ),
                icon    : 'icon-group',
                height  : 400,
                width   : 350,
                information : '<div class="group-sitemap"></div>',
                events  :
                {
                    onDrawEnd : this.$onWindowCreate,
                    onSubmit  : this.$onSubmit
                }
            });

            this.$Win.create();

            return this;
        },

        /**
         * event - window ope / create
         */
        $onWindowCreate : function()
        {
            var SitemapBody = this.$Win.getBody().getElement( '.group-sitemap' );

            if ( this.getAttribute( 'message' ) )
            {
                new Element('div', {
                    html : this.getAttribute( 'message' )
                }).inject( SitemapBody, 'before' );
            }

            this.$Map = new GroupSitemap({
                multible : this.getAttribute( 'multible' )
            }).inject( SitemapBody );
        },

        /**
         * Event: on submit
         */
        $onSubmit : function()
        {
            this.fireEvent( 'submit', [ this, this.$Map.getValues() ] );
        }

    });
});