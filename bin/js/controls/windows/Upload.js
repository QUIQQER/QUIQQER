/**
 * Upload window
 *
 * @fires onSubmit
 * @fires onCancel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/windows/Window
 * @class QUI.controls.windows.Upload
 * @package com.pcsg.qui.js.controls.windows
 */

define('controls/windows/Upload', [

    'controls/windows/Window',
    'controls/upload/Form',

    'css!controls/windows/Upload.css'

], function(QUI_Win)
{
    /**
     * @class QUI.controls.windows.Upload
     *
     * @fires onDrawEnd
     * @fires onClose
     *
     * @param {Object} options
     */
    QUI.controls.windows.Upload = new Class({

        Implements : [ QUI_Win ],
        Type       : 'QUI.controls.windows.Upload',

        Binds : [
            '$onSetAttribute'
        ],

        options: {
            'name' : false,
            'type' : 'modal',

            'left'   : false,
            'top'    : false,
            'width'  : false,
            'height' : false,
            'icon'   : URL_BIN_DIR +'16x16/actions/up.png',

            'autoclose'    : true,
            'text'         : false,
            'texticon'     : false,
            'information'  : false,
            'footerHeight' : false,

            'server_finish' : false, // the server side callback function if upload is finish
            'server_check'  : false, // the server side callback check function

            cancel_button : {
                text      : 'abbrechen',
                textimage : URL_BIN_DIR +'16x16/cancel.png'
            },

            ok_button : {
                text      : 'hochladen',
                textimage : URL_BIN_DIR +'16x16/actions/up.png'
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
            this.$Buttons = null;
            this.$Form    = null;

            // on set attribute event
            // if attributes were set after creation
            this.addEvent( 'onSetAttribute', this.$onSetAttribute );
        },

        /**
         * Create the body for the upload window
         *
         * @method QUI.controls.windows.Upload#onCreate
         */
        onCreate : function()
        {
            var Body;

            var Content = this.$Win.el.content,
                Footer  = this.$Win.el.footer,
                html    = '';

            Content.setStyles({
                padding: 20
            });

            var Wrapper = Content.getParent( '.mochaContentWrapper' );

            this.$Body = new Element('div', {
                'class' : 'qui-win-upload-body box',
                html   : '<div class="textbody">' +
                             '<h2 class="text">&nbsp;</h2>' +
                             '<div class="information">&nbsp;</div>' +
                         '</div>',
                styles : {
                    'float': 'left',
                    width  : '100%'
                }
            });

            this.$Body.inject( Content );

            if ( this.getAttribute( 'texticon' ) ) {
                this.setAttribute( 'texticon', this.getAttribute( 'texticon' ) );
            }

            if ( this.getAttribute( 'text' ) ) {
                this.setAttribute( 'text', this.getAttribute( 'text' ) );
            }

            if ( this.getAttribute( 'information' ) ) {
                this.setAttribute( 'information', this.getAttribute( 'information' ) );
            }

            // upload
            this.$Form = new QUI.controls.upload.Form({
                Drops  : [ Wrapper ],
                styles : {
                    margin : '20px 0 0 70px',
                    float  : 'left',
                    clear  : 'both'
                },
                Win     : this,
                Node    : Node,
                events  :
                {
                    onBegin : function(Control)
                    {

                    },

                    onComplete : function(Control)
                    {

                    },

                    /// drag drop events
                    onDragenter: function(event, Elm, Upload)
                    {
                        if ( !Elm.hasClass( 'mochaContentWrapper' ) ) {
                            Elm = Elm.getParent( '.mochaContentWrapper' );
                        }

                        if ( !Elm || !Elm.hasClass( 'mochaContentWrapper' ) ) {
                            return;
                        }

                        Elm.addClass( 'dragdrop-bg' );

                        event.stop();
                    },

                    onDragend : function(event, Elm, Upload)
                    {
                        Elm.removeClass( 'dragdrop-bg' );
                    }
                }
            });

            if ( this.getAttribute( 'server_finish' ) ) {
                this.$Form.setParam( 'onfinish', this.getAttribute( 'server_finish' ) );
            }

            if ( this.getAttribute( 'server_check' ) ) {
                this.$Form.setParam( 'onstart', this.getAttribute( 'server_check' ) );
            }

            this.$Form.inject( this.$Body );

            // buttons
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
        },

        /**
         * return the upload form control
         *
         * @method QUI.controls.windows.Upload#getForm
         * @return {QUI.controls.upload.Form|null}
         */
        getForm : function()
        {
            return this.$Form;
        },

        /**
         * Submit the window
         *
         * @method QUI.controls.windows.Upload#submit
         */
        submit : function()
        {
            this.fireEvent( 'submit', [ this ] );

            this.getForm().submit();

            if ( this.getAttribute( 'autoclose' ) ) {
                this.close();
            }
        },

        /**
         * event : on set attribute
         *
         * @param {String} attr - Name of the attribute
         * @param {unknown_type} value - Value
         */
        $onSetAttribute : function(attr, value)
        {
            if ( !this.$Body.getElement( '.textbody' ) ) {
                return;
            }

            if ( attr == 'texticon' )
            {
                Asset.image(value, {
                    onLoad : function(Node)
                    {
                        var Texticon = this.$Body.getElement( '.texticon' ),
                            Textbody = this.$Body.getElement( '.textbody' );

                        if ( !Texticon )
                        {
                            Texticon = new Element( 'img.texticon' );
                            Texticon.inject( Textbody, 'before' );
                        }

                        Textbody.setStyle(
                            'width',
                            this.$Body.getSize().x - Node.width - 20
                        );

                        Texticon.src = value;

                    }.bind( this )
                });

                return;
            }

            if ( attr == 'information' )
            {
                this.$Body
                    .getElement( '.information' )
                    .set( 'html', value );

                return;
            }

            if ( attr == 'text' )
            {
                this.$Body
                    .getElement('.text')
                    .set( 'html', value );

                return;
            }

        }
    });

    return QUI.controls.windows.Upload;
});
