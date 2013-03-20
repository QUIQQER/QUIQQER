/**
 * Global Menu
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module Menu
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('Menu', [

    'controls/contextmenu/Bar'

], function(QUI_MenuBar)
{
    "use strict";

    QUI.Menu = {

        Bar : null,

        /**
         * Load the menu
         */
        load : function()
        {
            this.Bar = new QUI_MenuBar().inject( $('menu-container') );

            // menü laden
            QUI.Ajax.get('ajax_menu', function(result, Ajax)
            {
                QUI.Menu.Bar.insert( result );
            });
        },

        /**
         * event on menu item click
         * @param {QUI.controls.contextmenu.Item} Item
         */
        click : function(Item)
        {
            Item.setAttribute( 'old_icon', Item.getAttribute( 'icon' ) );
            Item.setAttribute( 'icon', URL_BIN_DIR +'images/loader.gif' );

            if ( !Item.getAttribute( 'qui-xml-file' ) )
            {
                if ( Item.getAttribute( 'require' ) )
                {
                    require( [ Item.getAttribute( 'require' ) ], function(Call)
                    {
                        if ( typeOf( Call ) === 'function' ) {
                            Call();
                        }

                        if ( this.getAttribute( 'click' ) ) {
                            eval( '( '+ this.getAttribute( 'click' ) +'(Call); )' );
                        }

                        this.setAttribute( 'icon', this.getAttribute( 'old_icon' ) );
                    }.bind( Item ));

                    return;
                }

                eval( '( '+ Item.getAttribute( 'click' ) +'() )' );
                Item.setAttribute( 'icon', Item.getAttribute( 'old_icon' ) );

                return;
            }

            require([
                'controls/desktop/panels/XML'
            ], function(QUI_XMLPanel)
            {
                var Panel = new QUI_XMLPanel(
                    Item.getAttribute( 'qui-xml-file' )
                );

                Panel.addEvent('onCreateEnd', function(Panel)
                {
                    Item.setAttribute( 'icon', Item.getAttribute( 'old_icon' ) );
                }.bind( Item ));

                QUI.Controls.get( 'content-panel' )[ 0 ].appendChild(
                    Panel
                );
            });
        }

    };

    return QUI.Menu;
});