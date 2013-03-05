/**
 * Alert Window
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/windows/Window
 * @requires controls/buttons/Button
 *
 * @module controls/windows/Alert
 * @package com.pcsg.qui.js.controls.windows
 * @namespace QUI.controls.windows
 */

define('controls/windows/Alert', [

    'controls/windows/Window',
    'controls/buttons/Button',

    'css!controls/windows/Alert.css'

], function(QUI_Win, QUI_Button)
{
    /**
     * @class QUI.controls.windows.Alert
     *
     * @fires onClose
     *
     * @param {Object} options
     */
    QUI.controls.windows.Alert = new Class({

        Implements : [ QUI_Win ],

        options : {
            'name'  : false,        /** @member QUI.controls.windows.Alert#name */
            'type'  : 'modal',      /** @member QUI.controls.windows.Alert#type */
            'title' : '',           /** @member QUI.controls.windows.Alert#title */

            'left'   : false,       /** @member QUI.controls.windows.Alert#left */
            'top'    : false,       /** @member QUI.controls.windows.Alert#top */
            'width'  : false,       /** @member QUI.controls.windows.Alert#width */
            'height' : false,       /** @member QUI.controls.windows.Alert#height */
            'icon'   : false,       /** @member QUI.controls.windows.Alert#icon */

            'body' : false,         /** @member QUI.controls.windows.Alert#body */
            'footerHeight' : false, /** @member QUI.controls.windows.Alert#footerHeight */

            'texticon'    : false,  /** @member QUI.controls.windows.Alert#texticon */
            'text'        : false,  /** @member QUI.controls.windows.Alert#text */
            'information' : false   /** @member QUI.controls.windows.Alert#information */
        },

        initialize : function(options)
        {
            options = options || {};

            this.init( options );

            // defaults
            if ( this.getAttribute( 'name' ) === false ) {
                this.setAttribute( 'name', 'win'+ new Date().getMilliseconds() );
            }

            if ( this.getAttribute( 'width' ) === false ) {
                this.setAttribute( 'width', 500 );
            }

            if ( this.getAttribute( 'height' ) === false ) {
                this.setAttribute( 'height', 240 );
            }

            this.$Body    = null;
            this.$Win     = null;
            this.$Buttons = null;
        },

        /**
         * oncreate event, create the alert box
         */
        onCreate : function()
        {
            var Body;

            var Content = this.$Win.el.content,
                Footer  = this.$Win.el.footer,
                html    = '';

            if ( this.getAttribute( 'texticon' ) ) {
                html = html +'<img src="'+ this.getAttribute( 'texticon' ) +'" class="texticon" />';
            }

            html = html +'<div class="textbody">';

            if ( this.getAttribute( 'text' ) ) {
                html = html +'<h2>'+ this.getAttribute( 'text' ) +'</h2>';
            }

            if ( this.getAttribute( 'information' ) ) {
                html = html +'<div class="information">'+ this.getAttribute( 'information' ) +'</div>';
            }

            html = html +'</div>';

            this.$Body = new Element('div.alert-body', {
                html   : html,
                styles : {
                    margin: 10
                }
            });

            this.$Body.inject( Content );

            if ( this.getAttribute( 'texticon' ) )
            {
                Body = this.$Body.getElement( '.textbody' );
                Body.setStyles({
                    width : Body.getSize().x - this.$Body.getElement( '.texticon' ).getSize().x - 20
                });
            }

            new QUI.controls.buttons.Button({
                text      : 'OK',
                textimage : URL_BIN_DIR +'16x16/apply.png',
                Win       : this,
                styles    : {
                    'float' : 'right'
                },
                events :
                {
                    onClick   : function(Btn) {
                        Btn.getAttribute( 'Win' ).close();
                    }
                }
            }).inject( Footer );
        }
    });

    return QUI.controls.windows.Alert;
});