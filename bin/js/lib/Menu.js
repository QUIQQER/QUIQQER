/**
 * The Topic Menü
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @package com.pcsg.qui.js
 * @namespace QUI.lib
 */

define('lib/Menu', function()
{
    QUI.namespace( 'lib' );

    QUI.lib.Menu =
    {
        load : function()
        {
            // menü laden
            QUI.Ajax.get('ajax_menu', function(result, Ajax)
            {
                Ajax.getAttribute('Menu').addItems( result );
            }, {
                Menu : MUI.get('menu')
            });
        },

        click : function(Itm)
        {
            if ( !Itm.needle )
            {
                eval( Itm.click +'(Itm)' );
                return;
            }

            require([ Itm.needle ], function(Plgn)
            {
                if ( this.click )
                {
                    eval( this.click +'(Itm)' );
                    return;
                }

                if (typeof Plgn.open !== 'undefined') {
                    Plgn.open();
                }

            }.bind( Itm ));
        }
    };

    return QUI.lib.Menu;
});