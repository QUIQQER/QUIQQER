
/**
 * A file upload formular
 * the control creates a upload formular
 * the formular sends the selected file to the upload manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @fires onBegin [this]
 * @fires onComplete [this]
 * @fires onSubmit [Array, this]
 * @fires onInputDestroy
 * @fires onDragenter [event, DOMNode, QUI.controls.upload.Form]
 * @fires onDragleave [event, DOMNode, QUI.controls.upload.Form]
 * @fires onDragend [event, DOMNode, QUI.controls.upload.Form]
 * @fires onDrop [event, files, Elm, Upload]
 *
 * @requires controls/Control
 *
 * @module controls/upload/Form
 * @class QUI.controls.upload.Form
 * @package com.pcsg.qui.js.controls.upload
 */

define('controls/upload/Form', [

    'controls/Control',
    'controls/progressbar/Progressbar',
    'lib/media/Utils',
    'classes/request/Upload',

    'css!controls/upload/Form.css'

], function(Control)
{
    QUI.namespace( 'controls.upload' );

    /**
     * @class QUI.controls.upload.Form
     *
     * @param {Object} options
     */
    QUI.controls.upload.Form = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.upload.File',

        options : {
            action     : URL_LIB_DIR +'QUI/upload/bin/upload.php',
            method     : 'POST', // form method
            maxuploads : false,  // how many uploads are allowed
            multible   : false,  // are multible uploads allowed?
            uploads    : 1,      // how many upload fields would be shown on the beginning
            Drops      : [],     // DragDrop Elements to add files to the form
            sendbutton : false   // insert a send button
        },

        /**
         * constructor
         *
         * @fires onSubmit [FileList, this]
         * @fires onChange [FileList, this]
         */
        initialize : function(options)
        {
            if ( typeof options.params !== 'undefined' ) {
                this.setParams( options.params );
            }

            this.init( options );

            this.$Add    = null;
            this.$Elm    = null;
            this.$Form   = null;
            this.$Frame  = null;
            this.$files  = {};
            this.$params = {};

            this.addEvents({
                onDestroy : function()
                {
                    if ( this.$Form ) {
                        this.$Form.destroy();
                    }

                    if ( this.$Frame ) {
                        this.$Frame.destroy();
                    }
                },

                onInputDestroy : function()
                {
                    if ( !this.$Add ) {
                        return;
                    }

                    var elms = this.$Form.getElements( 'input[type="file"]' );

                    if ( this.getAttribute( 'maxuploads' ) === false ||
                         this.getAttribute( 'maxuploads' ).toInt() > elms.length )
                    {
                        this.$Add.enable();
                        return;
                    }

                }.bind( this )
            });
        },

        /**
         * Add a param to the param list
         * This param would be send with the form
         *
         * @method QUI.controls.upload.Form#addParam
         *
         * @param {String} param         - param name
         * @param {String|Integer} value - param value
         */
        setParam : function(param, value)
        {
            this.$params[ param ] = value;
        },

        /**
         * Adds params to the param list
         *
         * @param {Object} params - list of params
         */
        setParams : function(params)
        {
            var n;

            for ( n in param ) {
                this.addParam( n, param[n] );
            }
        },

        /**
         * Return a form param
         *
         * @return {unknown_type} Form parameter
         */
        getParam : function(n)
        {
            if ( typeof this.$params[n] !== 'undefined' ) {
                return this.$params[n];
            }

            return false;
        },

        /**
         * Return the form param
         *
         * @return {Object} list of params
         */
        getParams : function()
        {
            return this.$params;
        },

        /**
         * Create the Form DOMNode
         *
         * @method QUI.controls.upload.Form#create
         * @return {DOMNode} Form
         */
        create : function()
        {
            this.$Elm = new Element( 'div' );

            this.$Frame = new Element('iframe', {
                name   : 'upload'+ this.getId(),
                styles : {
                    position : 'absolute',
                    top      : -100,
                    left     : -100,
                    height   : 10,
                    width    : 10
                }
            });

            this.$Frame.inject( document.body );

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Form = new Element('form', {
                enctype : "multipart/form-data",
                method  : this.getAttribute( 'method' ),
                action  : this.getAttribute( 'action' ),
                target  : 'upload'+ this.getId()
            });

            this.$Form.addEvent('submit', function(event)
            {
                if ( typeof FileReader === 'undefined' ) {
                    return true;
                }

                event.stop();
                this.submit();
            }.bind( this ));

            this.$Form.inject( this.$Elm );


            if ( this.getAttribute( 'maxuploads' ) !== false &&
                 this.getAttribute( 'maxuploads' ) > 1 )
            {
                this.$Add = new QUI.controls.buttons.Button({
                    textimage : URL_BIN_DIR +'16x16/add.png',
                    text      : 'Upload hinzufügen',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute( 'Control' ).addInput();
                        }
                    },
                    styles : {
                        marginBottom : 20
                    }
                }).inject( this.$Form, 'bottom' );
            }

            if ( this.getAttribute( 'sendbutton' ) )
            {
                new QUI.controls.buttons.Button({
                    textimage : URL_BIN_DIR +'16x16/upload.png',
                    text    : 'Hochladen beginnen',
                    alt     : 'Upload starten',
                    title   : 'Upload starten',
                    Control : this,

                    events :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute( 'Control' ).submit();
                        }
                    },

                    styles : {
                        clear : 'both',
                        marginTop : '20px'
                    }
                }).inject( this.$Elm );
            }


            for ( var i = 0, len = this.getAttribute( 'uploads' ); i < len; i++ ) {
                this.addInput();
            }

            if ( this.getAttribute( 'Drops' ).length > 0 ) {
                this.$dragDropInit();
            }

            return this.$Elm;
        },

        /**
         * Adds an input upload field to the form
         */
        addInput : function()
        {
            if ( !this.$Form ) {
                return;
            }

            var elms = this.$Form.getElements( 'input[type="file"]' );

            if ( this.getAttribute( 'maxuploads' ) !== false &&
                 elms.length !== 0 &&
                 this.getAttribute( 'maxuploads' ) <= elms.length )
            {
                QUI.MH.addError(
                    'Es sind nur '+ this.getAttribute( 'maxuploads' ) +' Uploads erlaubt.'
                );

                return;
            }

            var Container = new Element( 'div.qui-form-upload' );

            var Upload = new Element('input', {
                type   : "file",
                name   : "files",
                events : {
                    change : this.$onInputChange.bind( this )
                },
                styles : {
                    display : 'inline'
                }
            }).inject( Container);

            new Element('div', {
                'class' : 'qui-form-fileinfo smooth radius5',
                alt     : 'Per Klick können Sie eine andere Datei auswählen ...',
                title   : 'Per Klick können Sie eine andere Datei auswählen ...',
                events  :
                {
                    click : function(event)
                    {
                        var Control   = this,
                            Container = event.target.getParent( '.qui-form-upload' ),
                            FileInfo  = Container.getElement( '.qui-form-fileinfo' ),
                            Input     = Container.getElement( 'input[type="file"]' ),
                            id        = Slick.uidOf( Input );

                        FileInfo.setStyle( 'background-image', '' );
                        FileInfo.set(' html', '' );

                        Input.setStyle( 'display', 'inline' );

                        if ( this.$files[ id ] ) {
                            delete this.$files[ id ];
                        }

                        Input.click();

                    }.bind( this )
                }
            }).inject( Container );


            // first child cannot be deleted
            if ( elms.length )
            {
                new QUI.controls.buttons.Button({
                    image     : URL_BIN_DIR +'10x10/cancel.png',
                    Container : Container,
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn)
                        {
                            Btn.getAttribute( 'Container' ).destroy();
                            Btn.getAttribute( 'Control' ).fireEvent( 'inputDestroy' );
                        }
                    },
                    styles : {
                        margin : '6px 0'
                    }

                }).inject( Container, 'top' );
            } else
            {
                // placeholder
                new Element('div', {
                    styles : {
                        'float' : 'left',
                        width   : 20,
                        height  : 20
                    }
                }).inject( Container, 'top' );
            }


            Container.inject( this.$Form );

            if ( this.$Add &&
                 this.getAttribute( 'maxuploads' ).toInt() <= elms.length + 1 )
            {
                this.$Add.disable();
            }
        },

        /**
         * Add an upload container to the form
         *
         * @param {File} File
         * @param {DOMNode} Parent - [optional] Parent Element
         */
        addUpload : function(File, Input)
        {
            if ( typeof Input === 'undefined' )
            {
                var list = this.$Form.getElements( 'input:display(inline)' );

                if ( !list.length )
                {
                    list = this.$Form.getElements( 'input[type="file"]' );

                    if ( this.getAttribute( 'multible' ) &&
                         this.getAttribute( 'maxuploads' ) > list.length )
                    {
                        this.addInput();
                        list = this.$Form.getElements( 'input:display(inline)' );
                    }
                }

                Input = list[0];
            }

            this.$files[ Slick.uidOf(Input) ] = File;

            var Container = Input.getParent( '.qui-form-upload' ),
                FileInfo  = Container.getElement( '.qui-form-fileinfo' );

            FileInfo.set( 'html', File.name );
            FileInfo.setStyle(
                'background-image',
                'url('+ QUI.lib.media.Utils.getIconByMimeType( File.type ) +')'
            );

            Input.setStyle('display', 'none');
        },

        /**
         * Create an info container element
         *
         * @return {DOMNode}
         */
        createInfo : function()
        {
            this.$Info = new Element('div', {
                html : '<div class="file-name">Uploading ...</div>' +
                       '<div class="upload-time"></div>' +
                       '<div class="progress"></div>',
                'class' : 'upload-manager-file box smooth'
            });

            this.$Progress = new QUI.controls.progressbar.Progressbar({
                startPercentage : 99
            });

            this.$Progress.inject( this.$Info.getElement( '.progress' ) );

            return this.$Info;
        },

        /**
         * Send the formular
         *
         * @method QUI.controls.upload.Form#submit
         */
        submit : function()
        {
            // FileReader is undefined, so no html5 upload available
            // use the normal upload
            if ( typeof FileReader === 'undefined' )
            {
                this.$Form.getElements( 'input[type="hidden"]' ).destroy();

                // create the params into the form
                var n;

                for ( n in this.$params )
                {
                    new Element('input', {
                        type  : 'hidden',
                        value : this.$params[ n ],
                        name  : n
                    }).inject( this.$Form );
                }

                new Element('input', {
                    type  : 'hidden',
                    value : this.getId(),
                    name  : 'uploadid'
                }).inject( this.$Form );

                // send upload to the upload manager
                QUI.UploadManager.injectForm( this );

                // and submit the form
                this.$Form.submit();
                this.fireEvent( 'begin', [ this ] );

                return;
            }

            this.fireEvent( 'submit', [ this.getFiles(), this ] );

            // send to upload manager
            var params = this.getParams();

            params.events = {
                onComplete : this.finish.bind( this )
            };

            QUI.UploadManager.uploadFiles(
                this.getFiles(),
                this.getParam( 'onfinish' ),
                params
            );
        },

        /**
         * Set the status to finish and fires the onFinish Event
         *
         * @param {QUI.controls.upload.File} File
         * @param {unknown_type} result - result of the upload
         */
        finish : function(File, result)
        {
            if ( typeof this.$Progress !== 'undefined' ) {
                this.$Progress.set( 100 );
            }

            if ( typeof this.$Info !== 'undefined' ) {
                this.$Info.getElement( '.file-name' ).set( 'html', 'Upload finish' );
            }

            this.fireEvent( 'complete', [ this, File, result ] );
        },

        /**
         * Return the selected File or FileList object
         *
         * @return {File|null}
         */
        getFile : function()
        {
            var files = this.getFiles();

            if ( files[ 0 ] ) {
                return files[ 0 ];
            }

            return null;
        },

        /**
         * Return the selected FileList
         *
         * @return {Array}
         */
        getFiles : function()
        {
            var i;
            var files  = [],
                _files = this.$files;

            for ( i in _files )
            {
                if ( _files.hasOwnProperty( i ) ) {
                    files.push( _files[ i ] );
                }
            }

            return files;
        },

        /**
         * on upload input change
         *
         * @param {DOMEvent} event
         */
        $onInputChange : function(event)
        {
            var Target = event.target,
                files  = Target.files;

            if ( typeof files === 'undefined' ) {
                return;
            }

            if ( !files.length || !files[0] ) {
                return;
            }

            this.addUpload( files[0], Target );
            this.fireEvent( 'change', [ this.getFiles(), this ] );
        },

        /**
         * Initialize the DragDrop events if drag drop supported
         */
        $dragDropInit : function()
        {
            new QUI.classes.request.Upload(this.getAttribute('Drops'), {

                onDragenter: function(event, Elm, Upload)
                {
                    this.fireEvent( 'dragenter', [ event, Elm, this ] );
                }.bind( this ),

                onDragleave: function(event, Elm, Upload)
                {
                    this.fireEvent( 'dragleave', [ event, Elm, this ] );
                }.bind( this ),

                onDragend : function(event, Elm, Upload)
                {
                    this.fireEvent( 'dragend', [ event, Elm, this ] );
                }.bind( this ),

                onDrop : function(event, files, Elm, Upload)
                {
                    if ( !files.length ) {
                        return;
                    }

                    if ( this.getAttribute( 'maxuploads' ) !== false &&
                         files.length > this.getAttribute( 'maxuploads' ) )
                    {
                        QUI.MH.addError(
                            'Es sind nur '+ this.getAttribute( 'maxuploads' ) +' Uploads erlaubt.'
                        );
                    }

                    // add to the list
                    for ( var i = 0, len = files.length; i < len; i++ ) {
                        this.addUpload( files[ i ] );
                    }

                    this.fireEvent( 'drop', [ event, files, Elm, this ] );
                    this.fireEvent( 'dragend', [ event, Elm, this ] );

                }.bind( this )
            });
        }
    });

    return QUI.controls.upload.Form;
});
