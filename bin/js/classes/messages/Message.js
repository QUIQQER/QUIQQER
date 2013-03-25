/**
 * Message parent class
 *
 * @event onClick [this, event]
 * @event onDestroy [this]
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/messages/Message
 * @package com.pcsg.qui.js.classes.messages
 * @namespace QUI.classes.messages
 */

define('classes/messages/Message', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.messages' );

    /**
     * @class QUI.classes.messages.Message
     *
     * @memberof! <global>
     */
    QUI.classes.messages.Message = new Class({

        Extends : DOM,
        Type    : 'QUI.classes.messages.Message',

        options: {
            message : '',
            styles  : false
        },

        initialize : function(options)
        {
            this.Elm = null;

            this.init( options );
        },

        /**
         * Destroy the message DOM Element
         *
         * @method QUI.classes.Messages.Message#destroy
         */
        destroy : function()
        {
            this.fireEvent( 'destroy', [ this ] );

            if ( this.Elm ) {
                this.Elm.destroy();
            }
        },

        /**
         * Returns the message DOM Element
         *
         * @method QUI.classes.Messages.Message#getElm
         * @return {DOMNode|null}
         */
        getElm : function()
        {
            return this.Elm;
        },

        /**
         * Create the DOMNode Element
         *
         * @method QUI.classes.Messages.Message#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.Elm = new Element('div', {
                html : '<span class="time">'+ new Date().strftime("%H:%M:%S") +'</span>' +
                       '<span class="msg">'+ this.getAttribute('message') +'</span>' +
                       '<span class="close">'+
                           QUI.Locale.get(
                               'quiqqer/controls',
                               'messages.closes'
                           ) +
                       '</span>',

                events :
                {
                    mouseover : function() {
                        this.setStyle( 'opacity', 0.5 );
                    },

                    mouseout : function() {
                        this.setStyle( 'opacity', 1 );
                    },

                    click : function(event)
                    {
                        this.fireEvent( 'click', [ this, event] );
                    }.bind(this)
                }
            });

            if ( this.getAttribute( 'styles' ) ) {
                this.Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.Elm.getElement( '.close' ).addEvents({
                click : function()
                {
                    this.destroy();
                }.bind( this )
            });

            this.Elm.addClass( 'box-sizing' );
            this.Elm.addClass( 'message' );
            this.Elm.addClass( 'animated' );
            this.Elm.addClass( this.getType().toLowerCase().replace( '.', '-' ) );

            return this.Elm;
        },

        /**
         * Insert the Message DOMNode into the Parent
         *
         * @method QUI.classes.Messages.Message#inject
         * @param {DOMNode} Parent - Parent element
         * @param {String} pos     - [optional]
         *
         * @return {this}
         */
        inject : function(Parent, pos)
        {
            if ( !this.Elm ) {
                this.create();
            }

            this.Elm.inject( Parent, pos );

            return this;
        },

        /**
         * Pulse the DOMNode
         *
         * @return {this}
         */
        fadeIn : function()
        {
            if ( this.Elm )
            {
                this.Elm.removeClass( 'bounceIn' );
                this.Elm.addClass( 'bounceIn' );
            }

            return this;
        }
    });

    return QUI.classes.messages.Message;
});
