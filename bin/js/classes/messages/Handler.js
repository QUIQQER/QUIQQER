
/**
 * Message Handler
 * Manage / display messages for the user
 *
 * @requires classes/messages/Message
 * @requires classes/messages/Attention
 * @requires classes/messages/Error
 * @requires classes/messages/Information
 * @requires classes/messages/Success
 *
 * @module classes/messages/Handler
 * @package com.pcsg.qui.js.classes.messages
 * @namespace QUI.classes.messages
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/messages/Handler', [

    'classes/messages/Message',
    'classes/messages/Attention',
    'classes/messages/Error',
    'classes/messages/Information',
    'classes/messages/Success',

    'css!classes/messages/Messages.css'

], function(Message, Attention, Error, Information, Success)
{
    "use strict";

    QUI.namespace( 'classes.messages' );

    /**
     * QUIQQER Message Handler
     *
     * Manage / display messages for the user
     */
    QUI.MH = QUI.MessagesHandler = // QUI.MH = Kurzschreibweise
    {
        Fx : null,

        Elm       : null,
        Content   : null,
        Container : null,
        Buttons   : null,
        MinMax    : null,
        Setting   : null,

        messages : [],

        options : {
            showmax : 5
        },

        load : function()
        {
            QUI.addEvent('onError', function(Exception, params)
            {
                // not loged in
                if ( Exception.getCode() === 401 )
                {
                    require(['lib/login/Login'], function(Login)
                    {
                        Login.show( this.getMessage() );
                    }.bind( Exception ));

                    return;
                }

                QUI.MH.addException( Exception );
            });
        },

        /**
         * Add a message to the handler
         *
         * @param {
         *      QUI.classes.messages.Error|
         *      QUI.classes.messages.Attention|
         *      QUI.classes.messages.Information|
         *      QUI.classes.messages.Message
         * } Message
         */
        add : function(Message)
        {
            this.messages.push( Message );

            if ( $('modalOverlay') && $('modalOverlay').getStyle('display') !== 'none' )
            {
                var Div = Message.create();

                Div.setStyles({
                    width    : 400,
                    position : 'absolute',
                    bottom   : 10,
                    right    : 20,
                    zIndex   : 10000,
                    padding  : 10,
                    opacity  : 0
                });

                Div.inject( document.body );

                moofx( Div ).animate({
                    opacity  : 1,
                    bottom   : 20
                });

                (function()
                {
                    moofx( this ).animate({
                        opacity  : 0
                    }, {
                        callback : function() {
                            this.destroy();
                        }.bind( this )
                    });
                }).delay(2000, Div);
            }


            // falls panel vorhanden ist, hier anzeigen
            if ( QUI.Controls.get( 'error-console' ).length )
            {
                var MH = QUI.Controls.get( 'error-console' )[ 0 ];

                Message.create().inject(
                    MH.getBody(), 'top'
                );

                Message.fadeIn();
            }

            // console logging
            if ( !QUI.config( 'globals' ) ||
                 !QUI.config( 'globals' ).development )
            {
                return;
            }

            switch ( Message.getType() )
            {
                case 'Message.Attention':
                    console.warn( Message.getAttribute( 'message' ) );
                break;

                case 'Message.Error':
                    console.error( Message.getAttribute( 'message' ) );
                break;

                case 'Message.Information':
                    console.info( Message.getAttribute( 'message' ) );
                break;

                default:
                    console.log( Message.getAttribute( 'message' ) );
            }
        },

        /**
         * Parse an array / object to their message type
         *
         * @return {
         *      QUI.classes.messages.Error|
         *      QUI.classes.messages.Attention|
         *      QUI.classes.messages.Information|
         *      QUI.classes.messages.Message
         * }
         */
        parse : function(params)
        {
            if ( params.type == 'QException' )
            {
                return new QUI.classes.messages.Error({
                    message : params.message,
                    code    : params.code
                });
            }

            if ( params.type == 'Attention' ||
                 params.type == 'QUI_Messages_Attention' )
            {
                return new QUI.classes.messages.Attention({
                    message : params.message
                });
            }

            if ( params.type == 'Error' ||
                 params.type == 'QUI_Messages_Error' )
            {
                return new QUI.classes.messages.Error({
                    message : params.message
                });
            }

            if ( params.type == 'Information' ||
                 params.type == 'QUI_Messages_Information' )
            {
                return new QUI.classes.messages.Information({
                    message : params.message
                });
            }

            if ( params.type == 'Success' ||
                 params.type == 'QUI_Messages_Success' )
               {
                   return new QUI.classes.messages.Success({
                       message : params.message
                   });
               }

            return new QUI.classes.messages.Message({
                message : params.message
            });
        },

        /**
         * Add an Exception to the Message Handler
         *
         * @param {Exception} Exception
         */
        addException : function(Exception)
        {
            this.add(
                new QUI.classes.messages.Error({
                    message : Exception.getMessage(),
                    code    : Exception.getCode()
                })
            );
        },

        /**
         * Add an Attention message to the Handler
         *
         * @param {String} str - attention message
         */
        addAttention : function(str)
        {
            this.add(
                new QUI.classes.messages.Attention({
                    message : str
                })
            );
        },

        /**
         * Add an Error to the Handler
         *
         * @param {String} str - error message
         */
        addError : function(str)
        {
            this.add(
                new QUI.classes.messages.Error({
                    message : str
                })
            );
        },

        /**
         * Add an Information to the Handler
         *
         * @param {String} str - information message
         */
        addInformation : function(str)
        {
            this.add(
                new QUI.classes.messages.Information({
                    message : str
                })
            );
        },

        /**
         * Add an Success message to the Handler
         *
         * @param {String} str - success message
         */
        addSuccess : function(str)
        {
            this.add(
                new QUI.classes.messages.Success({
                    message : str
                })
            );
        }
    };

    return QUI.MH;
});