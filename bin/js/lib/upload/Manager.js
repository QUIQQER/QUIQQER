/**
 * Upload manager
 * Uploads files and show the upload status
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module lib/upload/Manager
 * @package com.pcsg.qui.js
 * @namespace QUI
 */

define('lib/upload/Manager', [

    'controls/progressbar/Progressbar',
    'controls/upload/File',

    'css!lib/upload/Manager.css'

], function()
{
    "use strict";

    QUI.UploadManager = {

        $files     : [],
        $container : null,
        $uploads   : {},

        /**
         * Open the Upoad Manager in the right column
         */
        create : function()
        {
            if ( $('upload-manager-content') ) {
                return;
            }

            QUI.Workspace.appendPanel(
                new QUI.controls.desktop.Panel({
                    name    : 'upload-manager',
                    title   : 'Datei Upload Manager',
                    height  : 400,
                    content : '<div id="upload-manager-content"></div>'
                })
            );
            /*
            var Parent = QUI.Controls.get( 'right-colum' )[0];

            Parent.appendChild(
                new QUI.controls.desktop.Panel({
                    name    : 'upload-manager',
                    title   : 'Datei Upload Manager',
                    height  : 400,
                    content : '<div id="upload-manager-content"></div>'
                })
            );
            */
        },

        /**
         * Send a Message to the Message Handler
         *
         * @param {Array} message -
         */
        sendMessage : function(message)
        {
            QUI.MH.add(
                QUI.MH.parse( message )
            );
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
         * @method QUI.UploadManager#uploadFiles
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

            this.create();

            // application/zip
            var i, len;

            var found_package_files = false,
                archive_files       = [],
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
                        found_package_files = true;
                        archive_files.push( files[i] );
                    }
                }
            }

            if ( found_package_files )
            {
                var list = '';

                for ( i = 0, len = archive_files.length; i < len; i++ )
                {
                    list = list +'<div>' +
                            '<input id="upload-file-'+ i +'" type="checkbox" value="'+ archive_files[i].name +'" />' +
                            '<label for="upload-file-'+ i +'" style="line-height: 20px; margin-left: 10px;">'+
                                archive_files[i].name +' entpacken' +
                            '</label>'+
                        '</div>';
                }


                // ask for extraction
                QUI.Windows.create('alert', {
                    title       : 'Archiv Dateien gefunden',
                    text        : 'Sie möchten folgende Archivdateien hochladen. ' +
                                  'Möchten Sie diese direkt entpacken?',
                    information : list,
                    files       : files,
                    rf          : rf,
                    params      : params,
                    Control     : this,
                    events      :
                    {
                        onClose : function(Win)
                        {
                            var i, n, len;

                            var Body      = Win.getBody(),
                                checkboxs = Body.getElements( 'input[type="checkbox"]' ),
                                files     = Win.getAttribute( 'files' ),
                                params    = Win.getAttribute( 'params' ),
                                rf        = Win.getAttribute( 'rf' ),
                                Control   = Win.getAttribute( 'Control' ),

                                extract = {};


                            // collect all which must be extract
                            for ( i = 0, len = checkboxs.length; i < len; i++ )
                            {
                                if ( checkboxs[i].checked ) {
                                    extract[ checkboxs[i].get( 'value' ) ] = true;
                                }
                            }

                            params.extract = extract;

                            Control.uploadFiles( files, rf, params );
                        }
                    }
                });

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

                var QUI_File = new QUI.controls.upload.File( files[ i ], {
                    phpfunc : rf,
                    params  : file_params,
                    events  : events
                });

                if ( file_params.phponstart ) {
                    QUI_File.setAttribute( 'phponstart', file_params.phponstart );
                }

                this.$files.push( QUI_File );

                QUI_File.inject( $('upload-manager-content'), 'top');
                QUI_File.upload();

                events = false;
            }
        },

        /**
         * Starts a none html5 upload
         *
         * @param {QUI.controls.upload.Form} Form - Upload form object
         */
        injectForm : function(Form)
        {
            Form.createInfo().inject( $('upload-manager-content'), 'top');

            this.$uploads[ Form.getId() ] = Form;
        },

        /**
         * Check if unfinished uploads exist from the user
         *
         * @method QUI.UploadManager#getUnfinishedUploads
         */
        getUnfinishedUploads : function()
        {
            QUI.Ajax.get('ajax_uploads_unfinished', function(files, Request)
            {
                if ( !files.length ) {
                    return;
                }

                var i, len, QUI_File, params,
                    func_oncancel, func_oncomplete;

                QUI.MH.addInformation(
                    'Sie haben nicht fertig gestellte Uploads im Upload Manager. ' +
                    'Führen Sie die Uploads bitte fort oder brechen Sie diese ab.'
                );

                if ( MUI.get('upload-manager') ) {
                    //MUI.get('upload-manager').collapse();
                }

                // events
                func_oncancel = function(File)
                {
                    QUI.Ajax.post('ajax_uploads_cancel', function(result, Request)
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

                    QUI_File = new QUI.controls.upload.File(params.file, {
                        phpfunc : params.phpfunc,
                        params  : params,
                        events  :
                        {
                            onComplete : func_oncomplete,
                            onCancel   : func_oncancel
                        }
                    });

                    QUI_File.inject( $('upload-manager-content'), 'top');
                    QUI_File.refresh();
                }
            });
        }
    };

    return QUI.UploadManager;
});