
/**
 * A file upload control for the upload manager
 * it shows the upload status for one file
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/upload/File
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/contextmenu/Menu
 * @require qui/controls/contextmenu/Item
 * @require qui/controls/buttons/Button
 * @require qui/controls/utils/Progressbar
 * @require qui/controls/windows/Prompt
 * @require qui/controls/messages/Error
 * @require qui/utils/Math
 * @require qui/utils/Object
 * @require Ajax
 * @require Locale
 *
 * @fires onClick [this]
 * @fires onCancel [this]
 * @fires onComplete [this]
 * @fires onError [qui/controls/messages/Error, this]
 * @fires onRefresh [this, {Number} percent]
 */

define('controls/upload/File', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/contextmenu/Menu',
    'qui/controls/contextmenu/Item',
    'qui/controls/buttons/Button',
    'qui/controls/utils/Progressbar',
    'qui/controls/windows/Confirm',
    'qui/controls/messages/Error',
    'qui/utils/Math',
    'qui/utils/Object',
    'Ajax',
    'Locale'

], function()
{
    "use strict";

    var lg = 'quiqqer/system';

    var QUI                = arguments[ 0 ],
        QUIControl         = arguments[ 1 ],
        QUIContextMenu     = arguments[ 2 ],
        QUIContextmenuItem = arguments[ 3 ],
        QUIButton          = arguments[ 4 ],
        QUIProgressbar     = arguments[ 5 ],
        QUIConfirm          = arguments[ 6 ],
        MessageError       = arguments[ 7 ],
        MathUtils          = arguments[ 8 ],
        ObjectUtils        = arguments[ 9 ],
        Ajax               = arguments[ 10 ],
        Locale             = arguments[ 11 ];


    /**
     * @class controls/upload/File
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/upload/File',

        Binds : [
            'upload'
        ],

        options : {
            phpfunc    : '',
            phponstart : '', // (optional) php function which called before the upload starts
            params  : {}
        },

        $File     : false,
        $Progress : false,

        /**
         * constructor
         *
         * @method controls/upload/File#initialize
         *
         * @param {File} File      - a html5 file object
         * @param {Object} options - request options
         */
        initialize : function(File, options)
        {
            this.$File = File;

            if ( !this.$File.size || !this.getFilename() )
            {
                this.$File = false;

                QUI.getMessageHandler(function(MessageHandler)
                {
                    MessageHandler.addError(
                        Locale.get( lg, 'file.message.corrupt.file' )
                    );
                });

                return;
            }

            var self = this;

            this.$is_paused    = false;
            this.$file_size    = this.$File.size;
            this.$chunk_size   = ( 1024 * 100 );
            this.$range_start  = 0;
            this.$range_end    = this.$chunk_size;
            this.$upload_time  = null;
            this.$execute      = true; // false if no excute of the update routine
            this.$result       = null;
            this.$error        = false;


            this.$slice_method = 'slice';

            if ( 'mozSlice' in this.$File )
            {
                this.$slice_method = 'mozSlice';
            } else if ( 'webkitSlice' in this.$File )
            {
                this.$slice_method = 'webkitSlice';
            }

            this.$Request = new XMLHttpRequest();
            this.$Request.onload = function() {
                self.upload();
            };

            // check server answer
            this.$Request.onreadystatechange = function()
            {
                if ( self.$Request.readyState == 4 ) {
                    self.$parseResult( self.$Request.responseText );
                }
            };

            this.parent( options );

            // if something has already been uploaded
            // eg: the file is from the upload manager
            if ( 'uploaded' in this.$File )
            {
                this.$is_paused   = true;
                this.$range_start = this.$File.uploaded;
                this.$range_end   = this.$range_start + this.$chunk_size;

                if ( this.$Progress )
                {
                    this.$Progress.set(
                        MathUtils.percent(
                            this.$range_start,
                            this.$file_size
                        )
                    );
                }
            }
        },

        /**
         * Create the DOMNode
         *
         * @method controls/upload/File#create
         * @return {HTMLElement}
         */
        create : function()
        {
            var self = this;

            this.$Elm = new Element('div', {
                html : '<div class="file-name">'+ this.getFilename() +'</div>' +
                       '<div class="upload-time"></div>' +
                       '<div class="progress"></div>' +
                       '<div class="buttons"></div>',
                'class' : 'upload-manager-file box smooth'
            });

            this.$Elm.addEvents({
                click : function() {
                    self.fireEvent( 'click', [ self ] );
                },
                contextmenu : function(event)
                {
                    event.stop();

                    self.$ContextMenu.setPosition(
                        event.page.x,
                        event.page.y
                    );

                    self.$ContextMenu.show();
                    self.$ContextMenu.focus();
                }
            });

            this.$Progress = new QUIProgressbar();
            this.$Progress.inject( this.$Elm.getElement( '.progress' ) );


            var Buttons = this.$Elm.getElement('.buttons');

            Buttons.set({
                html : '<form action="" method=""">' +
                           '<input type="file" name="files" value="upload" />' +
                       '</form>',
                styles : {
                    'float' : 'right',
                    clear   : 'both',
                    margin  : '10px 0 0 0'
                }
            });

            Buttons.getElement('input[type="file"]').set({
                events :
                {
                    change : function(event)
                    {
                        var Target = event.target,
                            files  = Target.files;

                        if ( !files[0] ) {
                            return;
                        }

                        self.$File = files[0];
                        self.resume();
                    }
                },
                styles : {
                    bottom     : 0,
                    opacity    : 0,
                    position   : 'absolute',
                    right      : 0,
                    visibility : 'hidden'
                }
            });

            this.$Cancel = new QUIButton({
                name    : 'cancel-upload',
                text    : 'abbrechen',
                Control : this,
                events  :
                {
                    onClick : function()
                    {
                        self.pause();

                        new QUIConfirm({
                            name  : 'cancel-upload-window',
                            title : Locale.get( lg, 'file.upload.cancel.title' ),
                            text  : Locale.get( lg, 'file.upload.cancel.title' ),
                            information : Locale.get( lg, 'file.upload.cancel.information', {
                                file : self.getFilename()
                            }),
                            maxWidth  : 640,
                            maxheight : 360,
                            events :
                            {
                                onSubmit : function() {
                                    self.cancel();
                                },

                                onCancel : function() {
                                    //Win.getAttribute('Control').resume();
                                }
                            }
                        }).open();
                    }
                }
            });

            this.$PauseResume = new QUIButton({
                name    : 'continue-upload',
                text    : Locale.get( lg, 'pause' ),
                Control : this,
                events  :
                {
                    onClick : function()
                    {
                        if ( self.$is_paused )
                        {
                            self.resume();
                            return;
                        }

                        if ( !self.$is_paused ) {
                            self.pause();
                        }
                    }
                }
            });

            if ( this.$is_paused ) {
                this.$PauseResume.setAttribute( 'text', Locale.get( lg, 'resume' ) );
            }

            this.$Cancel.inject( Buttons );
            this.$PauseResume.inject( Buttons );

            // context menu
            this.$ContextMenu = new QUIContextMenu({
                title  : this.getFilename(),
                events :
                {
                    blur : function(Menu) {
                        Menu.hide();
                    }
                }
            });

            this.$ContextMenu.appendChild(
                new QUIContextmenuItem({
                    text   : Locale.get( lg, 'file.upload.remove' ),
                    File   : this,
                    events :
                    {
                        onClick : function(Item) {
                            Item.getAttribute( 'File' ).getElm().destroy();
                        }
                    }
                })
            );

            this.$ContextMenu.inject( document.body );

            // onerror, display it
            this.addEvent('onError', function(Exception, File)
            {
                var Elm = File.getElm();

                if ( !Elm ) {
                    return;
                }

                if ( Elm.getElement( '.progress' ) ) {
                    Elm.getElement( '.progress' ).destroy();
                }

                if ( Elm.getElement( '.buttons' ) ) {
                    Elm.getElement( '.buttons' ).destroy();
                }

                new Element('div', {
                    'class' : 'box',
                    html    : Exception.getMessage(),
                    styles  : {
                        clear   : 'both',
                        'float' : 'left',
                        width   : '100%',
                        padding    : '10px 0 0 20px',
                        background : 'url('+ URL_BIN_DIR +'16x16/error.png) no-repeat left center'
                    }
                }).inject( Elm );
            });

            return this.$Elm;
        },

        /**
         * Refresh the Progressbar
         *
         * @method controls/upload/File#refresh
         */
        refresh : function()
        {
            var percent = MathUtils.percent( this.$range_start, this.$file_size );

            this.fireEvent( 'refresh', [ this, percent ] );

            if ( !this.$Progress ) {
                return;
            }

            this.$Progress.set( percent );
        },

        /**
         * Start the upload of the file
         *
         * @method controls/upload/File#upload
         */
        upload : function()
        {
            if ( !this.$File ) {
                return;
            }

            if ( this.$File.type === '' || !this.$File.type )
            {
                QUI.getMessageHandler(function(MessageHandler)
                {
                    MessageHandler.addError(
                        Locale.get( lg, 'file.upload.unknown.filetype' )
                    );
                });

                return;
            }

            if ( this.$is_paused ) {
                return;
            }

            // set upload start time
            if ( !this.$upload_time )
            {
                var Now = new Date();

                this.$upload_time = Now.getHours() +':'+ Now.getMinutes();

                if ( this.$Elm )
                {
                    this.$Elm
                        .getElement('.upload-time')
                        .set('html', this.$upload_time);
                }
            }

            if ( this.$range_start >= this.$file_size ) {
                this.$execute = false;
            }

            if ( this.$execute === false )
            {
                MathUtils.percent( 100 );

                if ( this.$Cancel )
                {
                    this.$Cancel.destroy();
                    this.$Cancel = null;
                }

                if ( this.$PauseResume )
                {
                    this.$PauseResume.destroy();
                    this.$PauseResume = null;
                }

                if ( this.getElm().getElement('.buttons') ) {
                    this.getElm().getElement('.buttons').destroy();
                }

                if ( this.$error === false ) {
                    this.fireEvent( 'complete', [ this, this.$result ] );
                }

                return;
            }

            if ( this.$execute ) {
                this.$upload.delay( 25, this );
            }
        },

        /**
         * Set the upload to pause
         *
         * @method controls/upload/File#pause
         */
        pause : function()
        {
            this.$is_paused = true;

            if ( this.$PauseResume ) {
                this.$PauseResume.setAttribute('text', 'fortführen');
            }
        },

        /**
         * resume the upload
         *
         * @method controls/upload/File#resume
         */
        resume : function()
        {
            if ( this.$File instanceof File === false )
            {
                var Upload = this.getElm().getElement('input[type="file"]');

                if ( Upload ) {
                    Upload.click();
                }

                return;
            }

            if ( this.$PauseResume ) {
                this.$PauseResume.setAttribute('text', 'pause');
            }

            this.$is_paused = false;
            this.upload();
        },

        /**
         * Cancel the Upload
         *
         * @method controls/upload/File#cancel
         */
        cancel : function()
        {
            this.fireEvent('cancel', [this]);
        },

        /**
         * Return the File object
         *
         * @method controls/upload/File#upload
         * @return {File|Boolean}
         */
        getFile : function()
        {
            return this.$File;
        },

        /**
         * Return the name of the file
         *
         * @method controls/upload/File#getFilename
         * @return {String}
         */
        getFilename : function()
        {
            if ( !this.$File ) {
                return '';
            }

            return this.$File.name || '';
        },

        /**
         * Return the Upload status
         * is the upload is finish = true else false
         *
         * @method controls/upload/File#isFinished
         * @return {Boolean}
         */
        isFinished : function()
        {
            return this.$range_end === this.$file_size;
        },

        /**
         * Upload helper method
         *
         * @method controls/upload/File#$upload
         * @ignore
         */
        $upload : function()
        {
            if ( this.$execute === false ) {
                return;
            }

            if ( this.$range_end > this.$file_size )
            {
                this.$range_end = this.$file_size;
                this.$execute   = false;
            }

            // the file part
            var data = this.$File[ this.$slice_method ](
                    this.$range_start,
                    this.$range_end
                ),

                // extra params for ajax function
                params = ObjectUtils.combine( (this.getAttribute('params') || {}), {
                    file : JSON.encode({
                        uploadstart : this.$upload_time,
                        chunksize   : this.$chunk_size,
                        chunkstart  : this.$range_start
                    }),
                    onfinish : this.getAttribute('phpfunc'),
                    onstart  : this.getAttribute('phponstart'),
                    filesize : this.$file_size,
                    filename : this.getFilename(),
                    filetype : this.$File.type
                });

            if ( typeof params.lang === 'undefined' ) {
                params.lang = Locale.getCurrent();
            }

            // $project, $parentid, $file, $data
            var url = URL_LIB_DIR +'QUI/Upload/bin/upload.php?';
                url = url + Object.toQueryString( params );

            this.$Request.open( 'PUT', url, true );
            this.$Request.overrideMimeType( 'application/octet-stream' );

            if ( this.$range_start !== 0 )
            {
                this.$Request.setRequestHeader(
                    'Content-Range',
                    'bytes ' + this.$range_start +'-'+ this.$range_end +'/'+ this.$file_size
                );
            }

            this.$Request.send( data );


            // Update our ranges
            this.$range_start = this.$range_end;
            this.$range_end   = this.$range_start + this.$chunk_size;

            if ( this.$range_end > this.$file_size ) {
                this.$range_end = this.$file_size;
            }

            if ( this.$range_start > this.$file_size ) {
                this.$range_start = this.$file_size;
            }

            // set status
            this.refresh();
        },

        /**
         * Parse the request result from the server
         * send errors to the message handler and cancel the request if some errores exist
         *
         * @param {String} responseText - server answer
         *
         * @todo better to use the direct classes.request.Ajax.$parseResult method
         */
        $parseResult : function(responseText)
        {
            var str   = responseText || '',
                len   = str.length,
                start = 9,
                end   = len-10;

            if ( !len ) {
                return;
            }

            if ( !str.match('<quiqqer>') || !str.match('</quiqqer>') )
            {
                this.$error = true;

                return this.fireEvent('error', [
                    new MessageError({
                        message : 'No QUIQQER XML',
                        code    : 500
                    }),
                    this
                ]);
            }

            if ( str.substring(0, start) != '<quiqqer>' ||
                 str.substring(end, len) != '</quiqqer>' )
            {
                this.$error = true;

                return this.fireEvent('error', [
                    new MessageError({
                        message : 'No QUIQQER XML',
                        code    : 500
                    }),
                    this
                ]);
            }

            // callback
            var res;
            var result = eval( '('+ str.substring( start, end ) +')' );

            // exist messages?
            if ( result.message_handler &&
                 result.message_handler.length )
            {
                var messages = result.message_handler;

                QUI.getMessageHandler(function(MH)
                {
                    for ( var i = 0, len = messages.length; i < len; i++ )
                    {
                        MH.parse( messages[ i ], function(Message) {
                            MH.add( Message );
                        });
                    }
                });
            }

            // exist a main exception?
            if ( result.Exception )
            {
                this.$error = true;

                return this.fireEvent('error', [
                    new MessageError({
                        message : result.Exception.message || '',
                        code    : result.Exception.code || 0,
                        type    : result.Exception.type || 'Exception'
                    }),
                    this
                ]);
            }

            // result parsing
            res = result[ this.getAttribute('phpfunc') ];

            if ( !res ) {
                return;
            }

            if ( res.Exception )
            {
                this.$error = true;

                this.fireEvent('error', [
                    new MessageError({
                        message : res.Exception.message || '',
                        code    : res.Exception.code || 0,
                        type    : res.Exception.type || 'Exception'
                    }),
                    this
                ]);
            }

            if ( res.result ) {
                this.$result = res.result;
            }
        }
    });
});
