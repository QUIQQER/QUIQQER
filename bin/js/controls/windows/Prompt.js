/**
 * Submit Fenster
 *
 * @author www.pcsg.de (Henning Leutz)
 * @class QUI.controls.Windows.Prompt
 * @package com.pcsg.qui.js.controls.windows
 *
 * @event onSubmit [ value, this ]
 * @event onEnter [ value, this ]
 * @event onCancel [ this ]
 */

define('controls/windows/Prompt', [

    'controls/windows/Window',
    'controls/buttons/Button',

    'css!controls/windows/Submit.css'

], function(Win)
{
    QUI.namespace( 'controls.window' );

    /**
     * @class QUI.controls.windows.Prompt
     *
     * @fires onDrawEnd
     * @fires onClose
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.windows.Prompt = new Class({

        Implements : [ Win ],
        Type       : 'QUI.controls.windows.Prompt',

        options: {
            'name'  : false,
            'type'  : 'modal',
            'title' : '',

            'left'   : false,
            'top'    : false,
            'width'  : false,
            'height' : false,
            'icon'   : false,
            'check'  : false, // function to check the input
            'autoclose' : true,

            'footerHeight' : false,

            'text'        : false,
            'texticon'    : false,
            'information' : false,

            cancel_button : {
                text      : 'abbrechen',
                textimage : URL_BIN_DIR +'16x16/cancel.png'
            },
            ok_button : {
                text      : 'OK',
                textimage : URL_BIN_DIR +'16x16/apply.png'
            }
        },

        initialize : function(options)
        {
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
            this.$Input   = null;
            this.$Buttons = null;
        },

        /**
         * oncreate event, create the prompt box
         */
        onCreate : function(Win)
        {
            this.$Win = Win;

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

            html = html +'<input type="text" value="" />';

            if ( this.getAttribute( 'information' ) ) {
                html = html +'<div class="information">'+ this.getAttribute( 'information' ) +'</div>';
            }

            html = html +'</div>';

            this.$Body = new Element('div.submit-body', {
                html   : html,
                styles : {
                    margin: 10
                }
            });

            this.$Input = this.$Body.getElement( 'input' );

            this.$Input.setStyles({
                width   : 250,
                margin  : '10px auto',
                display : 'block'
            });

            this.$Input.addEvent('keyup', function(event)
            {
                if ( event.key === 'enter' )
                {
                    this.fireEvent( 'enter', [ this.getValue(), this ] );
                    this.submit();
                }
            }.bind( this ));

            this.$Body.inject( Content );


            // ondraw end
            if ( this.getAttribute( 'texticon' ) )
            {
                // damit das bild geladen wird und die proportionen da sind
                Asset.image(this.getAttribute('texticon'),
                {
                    onLoad: function()
                    {
                        var Texticon = this.$Body.getElement( '.texticon' ),
                            Textbody = this.$Body.getElement( '.textbody' );

                        Textbody.setStyle(
                            'width',
                            this.$Body.getSize().x - Texticon.getSize().x -20
                        );

                    }.bind(this)
                });
            }

            new QUI.controls.buttons.Button({
                text      : this.getAttribute( 'cancel_button' ).text,
                textimage : this.getAttribute( 'cancel_button' ).textimage,
                Win       : this,
                styles    : {
                    'float' : 'right'
                },
                events :
                {
                    onClick : function(Btn)
                    {
                        var Win = Btn.getAttribute( 'Win' );

                        Win.fireEvent( 'cancel', [ Win ] );
                        Win.close();
                    }
                }
            }).inject( Footer );

            new QUI.controls.buttons.Button({
                text      : this.getAttribute( 'ok_button' ).text,
                textimage : this.getAttribute( 'ok_button' ).textimage,
                Win       : this,
                styles    : {
                    'float' : 'right'
                },
                events :
                {
                    onClick : function(Btn) {
                        Btn.getAttribute( 'Win' ).submit();
                    }
                }
            }).inject( Footer, 'top' );

            // focus after 200 miliseconds
            (function() {
                this.$Input.focus();
            }).delay(200, this);
        },

        /**
         * Return the DOMNode input field of the prompt
         * @returns {DOMNode}
         */
        getInput : function()
        {
            return this.$Input;
        },

        /**
         * Return the value
         * @return {String}
         */
        getValue : function()
        {
            if ( !this.getInput() ) {
                return '';
            }

            return this.getInput().value;
        },

        /**
         * Set the value of the prompt
         *
         * @param value
         * @return {this}
         */
        setValue : function(value)
        {
            if ( !this.getInput() ) {
                return this;
            }

            this.getInput().value = value;

            return this;
        },

        /**
         * Checks if a submit can be triggered
         *
         * @return {Boolean}
         */
        check : function()
        {
            if ( this.getAttribute( 'check' ) ) {
                return this.getAttribute( 'check' )( this );
            }

            if ( this.$Input.value === '' ) {
                return false;
            }

            return true;
        },

        /**
         * Submit the prompt window
         *
         * @return {Boolean}
         */
        submit : function()
        {
            if ( this.check() === false ) {
                return false;
            }

            this.fireEvent( 'submit', [ this.$Input.value, this ] );

            if ( this.getAttribute( 'autoclose' ) ) {
                this.close();
            }

            return true;
        }
    });

    return QUI.controls.windows.Prompt;
});
