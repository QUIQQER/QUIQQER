
/**
 * A file upload control for the upload manager
 * it shows the upload status for one file
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 *
 * @module controls/upload/File
 * @class QUI.controls.upload.File
 * @package com.pcsg.qui.js.controls.upload
 */

define('controls/upload/File', [

    'controls/Control',
    'classes/request/Ajax',
    'Utils',
    'controls/contextmenu/Menu',
    'controls/contextmenu/Item'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.upload' );

    /**
     * @class QUI.controls.upload.File
     *
     * @fires onClick [this]
     * @fires onCancel [this]
     * @fires onComplete [this]
     * @fires onError [QUI.classes.exceptions.Exception, this]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.upload.File = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.upload.File',

        Binds : [
            'upload'
        ],

        options : {
            phpfunc    : '',
            phponstart : '', // [optional] php function which called before the upload starts
            params  : {}
        },

        $File     : null,
        $Progress : null,

        /**
         * constructor
         *
         * @method QUI.controls.upload.File#initialize
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

                QUI.MH.addError(
                    'Die Datei ist fehlerhaft und kann nicht hochgeladen werden. ' +
                    'Bitte laden Sie eine andere Datei hoch.'
                );

                return;
            }

            this.$is_paused    = false;
            this.$file_size    = this.$File.size;
            this.$chunk_size   = (1024 * 100);
            this.$range_start  = 0;
            this.$range_end    = this.$chunk_size;
            this.$upload_time  = null;
            this.$execute      = true; // false if no excute of the update routine
            this.$result       = null;


            this.$slice_method = 'slice';

            if ( 'mozSlice' in this.$File )
            {
                this.$slice_method = 'mozSlice';
            } else if ( 'webkitSlice' in this.$File )
            {
                this.$slice_method = 'webkitSlice';
            }

            this.$Request = new XMLHttpRequest();
            this.$Request.onload = function()
            {
                this.upload();
            }.bind( this );

            // check server answer
            this.$Request.onreadystatechange = function()
            {
                if ( this.$Request.readyState == 4 ) {
                    this.$parseResult( this.$Request.responseText );
                }
            }.bind( this );

            this.init( options );

            this.addEvent('onError', function(Exception) {
                QUI.MH.addException( Exception );
            });


            // if something has already been uploaded
            // eg: the file is from the upload manager
            if ( typeof this.$File.uploaded !== 'undefined' )
            {
                this.$is_paused   = true;
                this.$range_start = this.$File.uploaded;
                this.$range_end   = this.$range_start + this.$chunk_size;

                if ( this.$Progress )
                {
                    this.$Progress.set(
                        QUI.Utils.percent(
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
         * @method QUI.controls.upload.File#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                html : '<div class="file-name">'+ this.getFilename() +'</div>' +
                       '<div class="upload-time"></div>' +
                       '<div class="progress"></div>' +
                       '<div class="buttons"></div>',
                'class' : 'upload-manager-file box smooth'
            });

            this.$Elm.addEvents({

                click : function(event)
                {
                    this.fireEvent('click', [this]);
                }.bind( this ),

                contextmenu : function(event)
                {
                    event.stop();

                    this.$ContextMenu.setPosition(
                        event.page.x,
                        event.page.y
                    );
                    this.$ContextMenu.show();
                    this.$ContextMenu.focus();
                }.bind( this )
            });

            this.$Progress = new QUI.controls.progressbar.Progressbar();
            this.$Progress.inject( this.$Elm.getElement( '.progress' ) );


            var Buttons = this.$Elm.getElement('.buttons');

            Buttons.set({
                html :  '<form action="" method=""">' +
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

                        this.$File = files[0];
                        this.resume();

                    }.bind(this)
                },
                styles : {
                    opacity    : 0,
                    position   : 'absolute',
                    visibility : 'hidden'
                }
            });

            this.$Cancel = new QUI.controls.buttons.Button({
                name    : 'cancel-upload',
                text    : 'abbrechen',
                Control : this,
                events  :
                {
                    onClick : function(Btn)
                    {
                        var Control = Btn.getAttribute('Control');

                        Control.pause();

                        QUI.Windows.create('submit', {
                            name   : 'cancel-upload-window',
                            title  : 'Upload abbrechen',
                            text   : 'Möchten Sie den Upload abbrechen?',
                            information : '' +
                                'Möchten Sie den Upload der Datei '+
                                    '<b>'+ Control.getFilename() +'</b>'+
                                ' wirklich abbrechen?',
                            height  : 150,
                            Control : Control,
                            events  :
                            {
                                onSubmit : function(Win) {
                                    Win.getAttribute('Control').cancel();
                                },

                                onCancel : function(Win) {
                                    //Win.getAttribute('Control').resume();
                                }
                            }
                        });
                    }
                }
            });

            this.$PauseResume = new QUI.controls.buttons.Button({
                name    : 'continue-upload',
                text    : 'pause',
                Control : this,
                events  :
                {
                    onClick : function(Btn)
                    {
                        var Control = Btn.getAttribute('Control');

                        if ( Control.$is_paused )
                        {
                            Control.resume();
                            return;
                        }

                        if ( !Control.$is_paused ) {
                            Control.pause();
                        }
                    }
                }
            });

            if ( this.$is_paused ) {
                this.$PauseResume.setAttribute('text', 'forführen');
            }

            this.$Cancel.inject( Buttons );
            this.$PauseResume.inject( Buttons );

            // context menu
            this.$ContextMenu = new QUI.controls.contextmenu.Menu({
                title  : this.getFilename(),
                events :
                {
                    blur : function(Menu) {
                        Menu.hide();
                    }
                }
            });

            this.$ContextMenu.appendChild(
                new QUI.controls.contextmenu.Item({
                    text   : 'Von der Liste entfernen',
                    File   : this,
                    events :
                    {
                        onClick : function(Item, event) {
                            Item.getAttribute('File').getElm().destroy();
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
         * @method QUI.controls.upload.File#refresh
         */
        refresh : function()
        {
            if ( !this.$Progress ) {
                return;
            }

            this.$Progress.set(
                QUI.Utils.percent( this.$range_start, this.$file_size )
            );
        },

        /**
         * Start the upload of the file
         *
         * @method QUI.controls.upload.File#upload
         */
        upload : function()
        {
            if ( !this.$File ) {
                return;
            }

            if ( this.$File.type === '' || !this.$File.type )
            {
                QUI.MH.addError(
                    'Die Dateiart ist unbekannt. ' +
                    'Bitte wählen Sie für den Upload nur Dateien und Bilder aus.' +
                    'Ordner können nur gepackt hochgeladen werden.'
                );

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
                QUI.Utils.percent( 100 );

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

                this.fireEvent( 'complete', [ this, this.$result ] );
                return;
            }

            if ( this.$execute ) {
                this.$upload.delay( 25, this );
            }
        },

        /**
         * Set the upload to pause
         *
         * @method QUI.controls.upload.File#pause
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
         * @method QUI.controls.upload.File#resume
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
         * @method QUI.controls.upload.File#cancel
         */
        cancel : function()
        {
            this.fireEvent('cancel', [this]);
        },

        /**
         * Return the File object
         *
         * @method QUI.controls.upload.File#upload
         * @return {File}
         */
        getFile : function()
        {
            return this.$File;
        },

        /**
         * Return the name of the file
         *
         * @method QUI.controls.upload.File#getFilename
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
         * @method QUI.controls.upload.File#isFinished
         * @return {Bool}
         */
        isFinished : function()
        {
            return this.$range_end === this.$file_size ?  true : false;
        },

        /**
         * Upload helper method
         *
         * @method QUI.controls.upload.File#$upload
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
                params = QUI.Utils.combine( (this.getAttribute('params') || {}), {
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

            // $project, $parentid, $file, $data
            var url = URL_LIB_DIR +'QUI/upload/bin/upload.php?';
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
         * @param {String} str - server answer
         *
         * @todo better to use the direct classes.request.Ajax.$parseResult method
         */
        $parseResult : function(responseText, responseXML)
        {
            var i;

            var str   = responseText || '',
                len   = str.length,
                start = 9,
                end   = len-10;

            if ( !len ) {
                return;
            }

            if ( !str.match('<quiqqer>') || !str.match('</quiqqer>') )
            {
                return this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
                        message : 'No QUIQQER XML',
                        code    : 500
                    }),
                    this
                ]);
            }

            if ( str.substring(0, start) != '<quiqqer>' ||
                 str.substring(end, len) != '</quiqqer>' )
            {
                return this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
                        message : 'No QUIQQER XML',
                        code    :  500
                    }),
                    this
                ]);
            }

            // callback
            var res, func;

            var result = eval( '('+ str.substring( start, end ) +')' ),
                params = this.getAttribute( 'params' );

            // exist messages?
            if ( result.message_handler &&
                 result.message_handler.length &&
                 typeof QUI.MH !== 'undefined' )
            {
                var messages = result.message_handler;

                for ( i = 0, len = messages.length; i < len; i++ )
                {
                    QUI.MH.add(
                        QUI.MH.parse( messages[ i ] )
                    );
                }
            }

            // exist a main exception?
            if ( result.Exception )
            {
                return this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
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
                this.fireEvent('error', [
                    new QUI.classes.exceptions.Exception({
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

    return QUI.controls.upload.File;
});
