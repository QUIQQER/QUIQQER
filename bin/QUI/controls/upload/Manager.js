/**
 * Upload manager
 * Uploads files and show the upload status
 *
 * @author www.namerobot.com (Henning Leutz)
 *
 * @module controls/upload/Manager
 * @package com.pcsg.quiqqer
 */

define('controls/upload/Manager', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/utils/Progressbar',
    'qui/controls/windows/Alert',
    'controls/upload/File',
    'Ajax',

    'css!controls/upload/Manager.css'

], function(QUI, QUIPanel, QUIProgressbar, QUIAlert, UploadFile, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/upload/Manager',

        Binds : [
            '$onCreate'
        ],

        options : {
            title : 'Uploads',
            icon  : 'icon-upload'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$files     = [];
            this.$container = null;
            this.$uploads   = {};

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

            // this.create();

            // application/zip
            var i, len;

            var self                = this,
                foundPackageFiles = false,
                archiveFiles       = [],
                extract             = false;

            params = params || {};

            if ( params.extract === true ||
                 params.extract === false )
            {
                extract = params.extract;
            }

            if ( typeof params.extract === 'undefined' ) {
                extract = true;
            }

            if ( params.extract === true )
            {
                extract = {};

                for ( i = 0, len = files.length; i < len; i++ )
                {
                    if ( files[i].type === 'application/zip' ) {
                        extract[ files[i].name ] = true;
                    }
                }
            }

            // check for archive files (like zip or tar)
            // if undefined, ask for it
            if ( typeof params.extract === 'undefined' )
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
                                archiveFiles[i].name +' entpacken' +
                            '</label>'+
                        '</div>';
                }


                // ask for extraction
                new QUIAlert({
                    title       : 'Archiv Dateien gefunden',
                    text        : 'Sie möchten folgende Archivdateien hochladen. ' +
                                  'Möchten Sie diese direkt entpacken?',
                    information : list,
                    events      :
                    {
                        onClose : function(Win)
                        {
                            var i, n, len;

                            var Body      = Win.getBody(),
                                checkboxs = Body.getElements( 'input[type="checkbox"]' ),
                                extract   = {};


                            // collect all which must be extract
                            for ( i = 0, len = checkboxs.length; i < len; i++ )
                            {
                                if ( checkboxs[i].checked ) {
                                    extract[ checkboxs[i].get( 'value' ) ] = true;
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

                if ( file_params.phponstart ) {
                    QUIFile.setAttribute( 'phponstart', file_params.phponstart );
                }

                this.$files.push( QUIFile );

                if ( this.$Container ) {
                    QUIFile.inject( this.$Container, 'top');
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
                        'Sie haben nicht fertig gestellte Uploads im Upload Manager. ' +
                        'Führen Sie die Uploads bitte fort oder brechen Sie diese ab.'
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
        }

    });
});