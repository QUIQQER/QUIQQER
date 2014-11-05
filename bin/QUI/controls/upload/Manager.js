/**
 * Upload manager
 * Uploads files and show the upload status
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/upload/Manager
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/utils/Progressbar
 * @require qui/controls/windows/Alert
 * @require controls/upload/File
 * @require Ajax
 * @require Locale
 * @require css!controls/upload/Manager.css
 *
 * @event onFileComplete [ {self}, {File} ]
 * @event onFileUploadRefresh [ {self}, {Integer} percent ]
 */

define([

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/utils/Progressbar',
    'qui/controls/windows/Alert',
    'qui/utils/Math',
    'controls/upload/File',
    'Ajax',
    'Locale',

    'css!controls/upload/Manager.css'

], function(QUI, QUIPanel, QUIProgressbar, QUIAlert, MathUtils,  UploadFile, Ajax, Locale)
{
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * @class controls/upload/Manager
     *
     * @param {Object} options
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/upload/Manager',

        Binds : [
            '$onCreate',
            'uploadFiles',
            '$onFileUploadRefresh'
        ],

        options : {
            title : false,
            icon  : 'icon-upload'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$files      = [];
            this.$container  = null;
            this.$uploads    = {};

            this.$maxPercent     = 0;
            this.$uploadPerCents = {};

            this.$Container = null;

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * event : onCreate
         */
        $onCreate : function()
        {
            if ( !this.getAttribute( 'title' ) ) {
                this.setAttribute( 'title', Locale.get( lg, 'upload.manager.title' ) );
            }

            this.$Container = new Element('div', {
                'class' : 'upload-manager'
            }).inject( this.getContent() );
        },

        /**
         * Send a Message to the Message Handler
         *
         * @param {Array} message -
         */
        sendMessage : function(message)
        {
            QUI.getMessageHandler(function(MH)
            {
                MH.add(
                    MH.parse( message )
                );
            });
        },

        /**
         * Trigger function for the php upload
         */
        isFinish : function(uploadid)
        {
            // uploadid
            if ( this.$uploads[ uploadid ] ) {
                this.$uploads[ uploadid ].finish();
            }
        },

        /**
         * Upload files to the destination
         *
         * @method controls/upload/Manager#uploadFiles
         *
         * @param {Array} files - Array of file list
         * @param {String} php request function
         * @param {object} the params what would be send, too
         */
        uploadFiles : function(files, rf, params)
        {
            if ( typeof files === 'undefined' ) {
                return;
            }

            if ( !files.length ) {
                return;
            }

            // is an upload panel existent and open?
            if ( this.isOpen() === false )
            {
                if ( this.$Content )
                {
                    this.open();

                } else
                {
                    var Container = document.getElement(
                        '.qui-panel-content .upload-manager'
                    );

                    if ( Container )
                    {
                        var Content = Container.getParent();

                        if ( Content && Content.getStyle( 'display' ) == 'none' )
                        {
                            var Panel = QUI.Controls.getById(
                                Content.getParent( '.qui-panel' ).get( 'data-quiid' )
                            );

                            if ( Panel ) {
                                Panel.open();
                            }
                        }
                    }
                }
            }

            // application/zip
            var i, len;

            var self              = this,
                foundPackageFiles = false,
                archiveFiles      = [],
                extract           = false;

            params = params || {};

            if ( typeof params.extract !== 'undefined' ) {
                extract = params.extract;
            }

            // check for archive files (like zip or tar)
            // if undefined, ask for it
            if ( !extract )
            {
                for ( i = 0, len = files.length; i < len; i++ )
                {
                    if ( files[i].type === 'application/zip' )
                    {
                        foundPackageFiles = true;
                        archiveFiles.push( files[i] );
                    }
                }
            }

            if ( foundPackageFiles )
            {
                var list = '';

                for ( i = 0, len = archiveFiles.length; i < len; i++ )
                {
                    list = list +'<div>' +
                            '<input id="upload-file-'+ i +'" type="checkbox" value="'+ archiveFiles[i].name +'" />' +
                            '<label for="upload-file-'+ i +'" style="line-height: 20px; margin-left: 10px;">'+
                                Locale.get( lg, 'upload.manager.message.archivfile.label', {
                                    file: archiveFiles[i].name
                                })
                            '</label>'+
                        '</div>';
                }


                // ask for extraction
                new QUIAlert({
                    title   : Locale.get( lg, 'upload.manager.message.archivfile.title' ),
                    content : Locale.get( lg, 'upload.manager.message.archivfile.text' ) +'<br />'+ list,
                    closeButtonText : Locale.get( lg, 'upload.manager.message.archivfile.btn.start' ),
                    events      :
                    {
                        onClose : function(Win)
                        {
                            var i, n, len;

                            var Body      = Win.getContent(),
                                checkboxs = Body.getElements( 'input[type="checkbox"]' ),
                                extract   = {};


                            // collect all which must be extract
                            for ( i = 0, len = checkboxs.length; i < len; i++ )
                            {
                                if ( checkboxs[ i ].checked ) {
                                    extract[ checkboxs[ i ].get( 'value' ) ] = true;
                                }
                            }

                            params.extract = extract;

                            self.uploadFiles( files, rf, params );
                        }
                    }
                }).open();

                return;
            }


            var file_params;
            var events = false;

            this.$maxPercent = files.length * 100;

            for ( i = 0, len = files.length; i < len; i++ )
            {
                file_params = Object.clone( params );

                if ( extract && extract[ files[ i ].name ] )
                {
                    file_params.extract = true;
                } else
                {
                    file_params.extract = false;
                }

                if ( typeof file_params.events !== 'undefined' )
                {
                    events = file_params.events;

                    delete file_params.events;
                }

                var QUIFile = new UploadFile( files[ i ], {
                    phpfunc : rf,
                    params  : file_params,
                    events  : events
                });

                QUIFile.addEvents({
                    onComplete : function(File) {
                        self.fireEvent( 'fileComplete', [ self, File ] );
                    },
                    onRefresh : function(File, percent)
                    {
                        self.$uploadPerCents[ File.getId() ] = percent;
                        self.$onFileUploadRefresh();
                    }
                });

                if ( file_params.phponstart ) {
                    QUIFile.setAttribute( 'phponstart', file_params.phponstart );
                }

                this.$files.push( QUIFile );

                if ( this.$Container )
                {
                    QUIFile.inject( this.$Container, 'top');

                } else
                {
                    // exist upload container? ... not nice but functional
                    var Container = document.getElement( '.qui-panel-content .upload-manager' );

                    if ( Container ) {
                        QUIFile.inject( Container, 'top');
                    }
                }

                QUIFile.upload();

                events = false;
            }
        },

        /**
         * Starts a none html5 upload
         *
         * @param {controls/upload/Form} Form - Upload form object
         */
        injectForm : function(Form)
        {
            if ( this.$Container ) {
                Form.createInfo().inject( this.$Container, 'top');
            }

            this.$uploads[ Form.getId() ] = Form;
        },

        /**
         * Check if unfinished uploads exist from the user
         *
         * @method controls/upload/Manager#getUnfinishedUploads
         */
        getUnfinishedUploads : function()
        {
            Ajax.get('ajax_uploads_unfinished', function(files, Request)
            {
                if ( !files.length ) {
                    return;
                }

                var i, len, QUIFile, params,
                    func_oncancel, func_oncomplete;

                QUI.getMessageHandler(function(MH)
                {
                    MH.addInformation(
                        Locale.get( lg, 'upload.manager.message.not.finish' )
                    );
                });

                // events
                func_oncancel = function(File)
                {
                    Ajax.post('ajax_uploads_cancel', function(result, Request)
                    {
                        File.destroy();
                    }, {
                        file : File.getFilename()
                    });
                };

                func_oncomplete = function(File)
                {

                };

                // create
                for ( i = 0, len = files.length; i < len; i++ )
                {
                    if ( !files[i].params ) {
                        continue;
                    }

                    params = files[i].params;

                    if ( !params.phpfunc ) {
                        // @todo trigger error
                        continue;
                    }

                    if ( !params.file ) {
                        // @todo trigger error
                        continue;
                    }

                    QUIFile = new UploadFile(params.file, {
                        phpfunc : params.phpfunc,
                        params  : params,
                        events  :
                        {
                            onComplete : func_oncomplete,
                            onCancel   : func_oncancel
                        }
                    });

                    if ( this.$Container ) {
                        QUIFile.inject( this.$Container, 'top');
                    }

                    QUIFile.refresh();
                }
            });
        },

        /**
         * event : on file upload refresh
         * display the percent of the upload
         */
        $onFileUploadRefresh : function()
        {
            var percent = MathUtils.percent(
                Object.values( this.$uploadPerCents ).sum(),
                this.$maxPercent
            );

            this.fireEvent( 'fileUploadRefresh', [ this, percent ] );
        }
    });
});
