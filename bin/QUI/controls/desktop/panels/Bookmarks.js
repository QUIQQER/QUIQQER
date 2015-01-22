
/**
 * QUIQQER Bookmars
 *
 * @module controls/desktop/panels/Bookmarks
 * @author www.pcsg.de (Henning Leutz)
 */

define(['qui/controls/bookmarks/Panel'], function(QUIBookmarks)
{
    "use strict";

    return new Class({

        Extends : QUIBookmarks,
        Type    : 'controls/desktop/panels/Bookmarks',

        /**
         * overwrite appendChild, because we must use some special click events
         */
        appendChild : function(Item)
        {
            if ( !this.$Container ) {
                return this;
            }

            var Child;

            // parse qui/controls/contextmenu/Item to an Bookmark
            if ( Item.getType() == 'qui/controls/contextmenu/Item' )
            {
                var path    = Item.getPath(),
                    xmlFile = Item.getAttribute( 'qui-xml-file' );

                if ( xmlFile ) {
                    path = 'xmlFile:'+ xmlFile;
                }

                Child = this.$createEntry({
                    text  : Item.getAttribute( 'text' ),
                    icon  : Item.getAttribute( 'icon' ),
                    path  : path,
                    click : 'BookmarkPanel.xmlMenuClick(path)'
                }).inject( this.$Container );

            } else if ( Item.getType() == 'qui/controls/sitemap/Item' )
            {
                var ProjectSitemap = Item.getMap().getParent(),

                    project = ProjectSitemap.getAttribute( 'project' ),
                    lang    = ProjectSitemap.getAttribute( 'lang' ),
                    value   = Item.getAttribute('value');

                var click = 'require(["utils/Panels"], function(U) { U.openSitePanel( "'+ project +'", "'+ lang +'", "'+ value +'" ) })',
                    text  = Item.getAttribute( 'text' );

                if ( value === 'media' )
                {
                    click = 'require(["utils/Panels"], function(U) { U.openMediaPanel( "'+ project +'" ) })';
                    text  = Item.getAttribute( 'text' ) +' ('+ project +')';
                }

                Child = this.$createEntry({
                    text  : text,
                    icon  : Item.getAttribute( 'icon' ),
                    click : click,
                    path  : ''
                }).inject( this.$Container );

            } else
            {
                Child = this.$createEntry({
                    text  : Item.getAttribute( 'text' ),
                    icon  : Item.getAttribute( 'icon' ),
                    click : Item.getAttribute( 'bookmark' ),
                    path  : ''
                }).inject( this.$Container );
            }

            this.$bookmarks.push( Child );

            this.fireEvent( 'appendChild', [ this, Child ] );

            return this;
        },

        /**
         * XML menu click
         * @param {String} path - path of the xml file eq: xmlFile:path/settings.xml
         */
        xmlMenuClick : function(path)
        {
            if ( path.match( 'xmlFile:' ) )
            {
                require([
                    'Menu',
                    'controls/desktop/panels/XML'
                ], function(Menu, XMLPanel)
                {
                    Menu.openPanelInTasks(
                        new XMLPanel( path.substr( 8 ) )
                    );
                });
            }
        }
    });
});