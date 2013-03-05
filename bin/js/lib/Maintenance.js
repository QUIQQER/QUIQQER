/**
 * Maintenance Mode
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module lib/Maintenance
 * @package com.pcsg.qui.js
 * @namespace QUI.lib
 */

define('lib/Maintenance', function()
{
    QUI.namespace( 'lib' );

    QUI.lib.Maintenance =
    {
        /**
         * Open the Maintenance submit window
         *
         * @method QUI.lib.Maintenance#open
         */
        open : function()
        {
            QUI.Windows.create('submit', {

                title  : 'Wartungsarbeiten',
                icons  : URL_BIN_DIR +'16x16/configure.png',
                height : 150,
                autoclose : false,
                events :
                {
                    onDrawEnd : function(Win)
                    {
                        Win.Loader.show();

                        var Body = Win.getBody(),
                            id   = String.uniqueID();

                        Body.set('html', '<input name="maintenance" type="checkbox" />'+
                                         '<label for="">Wartungsarbeiten aktivieren</label>');

                        Body.getElement('input').set({
                            id     : id,
                            styles : {
                                margin : 10
                            }
                        });

                        Body.getElement('label').set({
                            'for'  : id,
                            styles : {
                                lineHeight: 22,
                                cursor    : 'pointer'
                            }
                        });

                        this.status(function(result, Request)
                        {
                            this.getBody().getElement('input').checked = parseInt( result, 10 );
                            this.Loader.hide();

                        }.bind( Win ));
                    }.bind( this ),

                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        var Input = Win.getBody().getElement('input');

                        if ( !Input )
                        {
                            Win.close();
                            return;
                        }


                        if (Input.checked)
                        {
                            QUI.lib.Maintenance.turnOn(function(result, Request)
                            {
                                this.close();
                            }.bind( Win ));
                        } else
                        {
                            QUI.lib.Maintenance.turnOff(function(result, Request)
                            {
                                this.close();
                            }.bind( Win ));
                        }
                    }
                }
            });
        },

        status : function(oncomplete)
        {
            QUI.Ajax.post('ajax_maintenance_status', oncomplete);
        },

        /**
         * Turn the maintenance mode on
         *
         * @method QUI.lib.Maintenance#turnOn
         * @param {Function} oncomplete - callback Function
         */
        turnOn : function(oncomplete)
        {
            QUI.Ajax.post('ajax_maintenance_on', oncomplete);
        },

        /**
         * Turn the maintenance mode off
         *
         * @method QUI.lib.Maintenance#turnOff
         * @param {Function} oncomplete - callback Function
         */
        turnOff : function(oncomplete)
        {
            QUI.Ajax.post('ajax_maintenance_off', oncomplete);
        }
    };

    return QUI.lib.Maintenance;
});