/**
 * A panel where you can set bookmarks
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/panels/Bookmarks
 * @package com.pcsg.qui.js.controls.desktop.panels
 * @namespace QUI.controls.desktop.panels
 */

define('controls/desktop/panels/Bookmarks', [

    'controls/desktop/Panel',
    'css!controls/desktop/panels/Bookmarks.css'

], function(QUI_Panel)
{
    QUI.namespace( 'controls.desktop.panels' );

    /**
     * @class QUI.controls.desktop.panels.Bookmarks
     */
    QUI.controls.desktop.panels.Bookmarks = new Class({

        Implements : [ QUI_Panel ],
        Type       : 'QUI.controls.desktop.panels.Bookmarks',

        initialize: function(options)
        {
            this.init( options );

            this.Loader = new QUI.controls.loader.Loader();

            this.$Elm       = null;
            this.$Header    = null;
            this.$Footer    = null;
            this.$Content   = null;
            this.$Container = null;

            this.$bookmarks = [];

            this.addEvent('onDrawEnd', function()
            {
                this.$create();
                this.fireEvent( 'load', [ this ] );
            }.bind( this ));
        },

        /**
         * Save the bookmark panel to the workspace
         *
         * @return {Object} data
         */
        serialize : function()
        {
            var i, len, Bookmark;
            var bookmarks = [];

            for ( i = 0, len = this.$bookmarks.length; i < len; i++ )
            {
                Bookmark = this.$bookmarks[ i ];

                bookmarks.push({
                    text  : Bookmark.get( 'html' ),
                    icon  : Bookmark.getStyle( 'backgroundImage' ),
                    click : Bookmark.get( 'data-click' )
                });
            }

            return {
                attributes : this.getAttributes(),
                type       : this.getType(),
                bookmarks  : bookmarks
            };
        },

        /**
         * import the saved data
         *
         * @param {Object} data
         * @return {this}
         */
        unserialize : function(data)
        {
            this.setAttributes( data.attributes );

            if ( !this.$Container )
            {
                this.$serialize = data;
                return this;
            }

            var i, len, Bookmark;
            var bookmarks = data.bookmarks;

            if ( !bookmarks ) {
                return this;
            }

            for ( i = 0, len = bookmarks.length; i < len; i++ )
            {
                Bookmark = bookmarks[ i ];

                this.$bookmarks.push(
                    this.$createEntry({
                        text  : Bookmark.text,
                        icon  : Bookmark.icon,
                        click : Bookmark.click
                    }).inject( this.$Container )
                );
            }

            return this;
        },

        /**
         * Internal creation
         */
        $create : function()
        {
            this.$Container = new Element( 'div' ).inject( this.getBody() );

            if ( typeof this.$serialize !== 'undefined' ) {
                this.unserialize( this.$serialize );
            }
        },

        /**
         * Add a bookmarks
         *
         * @param {QUI.controls.Control} Item - A QUI control
         * @return {this}
         */
        appendChild : function(Item)
        {
            if ( !this.$Container ) {
                return this;
            }

            this.$bookmarks.push(
                this.$createEntry({
                    text  : Item.getAttribute( 'text' ),
                    icon  : Item.getAttribute( 'icon' ),
                    click : Item.getAttribute( 'bookmark' )
                }).inject( this.$Container )
            );

            return this;
        },

        /**
         * Remove a bookmarks
         */
        remove : function()
        {

        },

        /**
         * Create a bookmark with all events
         *
         * @param {Object} params - {text, icon, click}
         * @return {DOMNode}
         */
        $createEntry : function(params)
        {
            params.text  = params.text || '';
            params.icon  = params.icon || false;
            params.click = params.click || false;

            var Bookmark = new Element('div', {
                'class' : 'qui-bookmark box smooth',
                'html'  : params.text,
                'data-click' : params.click,
                events  :
                {
                    click : function()
                    {
                        var click = this.get( 'data-click' );

                        if ( typeof click === 'undefined' ) {
                            return;
                        }

                        var e = eval( '('+ click +')' );

                        if ( typeof e === 'function' ) {
                            e();
                        }
                    }
                }
            });

            if ( params.icon )
            {
                if ( params.icon.match( /url\(/ ) )
                {
                    Bookmark.setStyle( 'backgroundImage', params.icon );
                } else
                {
                    Bookmark.setStyle( 'backgroundImage', 'url('+ params.icon +')' );
                }
            }

            return Bookmark;
        }
    });

    return QUI.controls.desktop.panels.Bookmarks;
});