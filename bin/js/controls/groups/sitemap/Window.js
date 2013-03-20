/**
 * Groups Sitemapwindow
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @class QUI.controls.groups.sitemap.Window
 * @package com.pcsg.qui.js.controls.groups.sitemap.Window
 * @namespace QUI.controls.groups.sitemap
 *
 * @event onSubmit [this, values]
 */

define('controls/groups/sitemap/Window', [

    'controls/Control',
    'controls/groups/Sitemap',
    'controls/windows/Submit'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.groups.sitemap' );

    /**
     * @class QUI.controls.groups.sitemap.Window
     *
     * @memberof! <global>
     */
    QUI.controls.groups.sitemap.Window = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.groups.sitemap.Window',

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

            this.init( options );
        },

        /**
         * Return the the Window
         *
         * @return {QUI.controls.windows.Submit}
         */
        create : function()
        {
            this.$Win = new QUI.controls.windows.Submit({
                title   : this.getAttribute( 'title' ),
                text    : this.getAttribute( 'text' ),
                icon    : URL_BIN_DIR +'16x16/group.png',
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

            this.$Map = new QUI.controls.groups.Sitemap({
                multible : this.getAttribute( 'multible' )
            }).inject( SitemapBody );
        },

        /**
         * Event: on submit
         */
        $onSubmit : function()
        {
            this.fireEvent( 'submit', [
                this,
                this.$Map.getValues()
            ] );
        }

    });

    return QUI.controls.groups.sitemap.Window;
});