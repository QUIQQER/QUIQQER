/**
 * Displays a Media in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/Utils
 * @requires controls/projects/media/PanelDOMEvents
 *
 * @module controls/projects/media/Panel
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/FilePanel', [

    'controls/Control',
    'controls/Utils',
    'controls/projects/media/PanelDOMEvents',

    'css!controls/projects/media/FilePanel.css'

], function(QUI_Control)
{
    QUI.namespace( 'controls.projects.media' );

    /**
     * A Media-Panel, opens the Media in an Apppanel
     *
     * @class QUI.controls.projects.media.FilePanel
     *
     * @param {QUI.classes.projects.media.File} File
     * @param {Object} options
     */
    QUI.controls.projects.media.FilePanel = new Class({

        Implements : [ QUI_Control ],
        Type       : 'QUI.controls.projects.media.FilePanel',

        options : {
            id        : 'projects-media-file-panel',
            container : false,
            fileid    : false
        },

        initialize : function(File, options)
        {
            // default id
            this.setAttribute( 'id', 'projects-media-file-panel-'+ File.getId() );
            this.setAttribute( 'name', 'projects-media-file-panel-'+ File.getId() );

            this.init( options );

            this.$Panel = null;
            this.$File  = File;
            this.$Media = this.$File.getMedia();

            this.$DOMEvents = new QUI.controls.projects.media.PanelDOMEvents( this );

            this.create();
        },

        /**
         * Close and destroy the panel
         *
         * @method QUI.controls.projects.media.FilePanel#close
         */
        close : function()
        {
            this.destroy();
        },

        /**
         * Create the file panel
         * create a MUI.Apppanel and start the file loading
         *
         * @method QUI.controls.projects.media.FilePanel#create
         */
        create : function()
        {
            var Panel = new QUI.controls.desktop.Panel({
                id     : this.getAttribute( 'id' ),
                icon   : URL_BIN_DIR +'images/loader.gif',
                tabbar : true
            });

            QUI.Controls.get( 'content-panel' )[0].appendChild(
                Panel
            );

            this.$Panel = Panel;
            this.$Panel.Loader.show();
            this.$Panel.getBody().set( 'data-id', this.$File.getId() );

            this.$createTabs();
            this.$createButtons();

            QUI.Template.get('project_media_file', function(result, Request)
            {
                var FormElm;

                var Control = Request.getAttribute( 'Control' ),
                    Panel   = Control.$Panel,
                    File    = Control.$File,
                    Body    = Panel.getBody();

                Body.set(
                    'html',

                    '<form>' +
                        result +
                        '<div class="qui-media-file-preview"></div>' +
                    '</form>'
                );

                FormElm = Body.getElement( 'form' );

                QUI.controls.Utils.parse( FormElm );

                Control.refresh();
            }, {
                Control : this
            });
        },

        /**
         * Load the buttons and the tabs to the panel
         *
         * @method QUI.controls.projects.media.Panel#load
         */
        load : function()
        {
            var File        = this.$File,
                dimension   = '',
                icon        = URL_BIN_DIR +'16x16/media.png',
                CategoryBar = this.$Panel.getCategoryBar();

            if ( File.getAttribute( 'image_width' ) &&
                 File.getAttribute( 'image_height' ) )
            {
                dimension = File.getAttribute( 'image_width' ) +
                            ' x '+
                            File.getAttribute( 'image_height' );
            }

            if ( File.getAttribute( 'type' ) == 'image' ) {
                icon = URL_BIN_DIR +'16x16/extensions/image.png';
            }

            // if the file is not a image, no preview exist
            if ( File.getAttribute( 'type' ) !== 'image' ) {
                CategoryBar.getElement( 'preview' ).disable();
            }

            // set data to form
            QUI.lib.Utils.setDataToForm({
                    file_name      : File.getAttribute( 'name' ),
                    file_title     : File.getAttribute( 'title' ),
                    file_alt       : File.getAttribute( 'alt' ),
                    file_short     : File.getAttribute( 'short' ),
                    file_file      : File.getAttribute( 'file' ),
                    file_path      : File.getAttribute( 'path' ),
                    file_type      : File.getAttribute( 'type' ),
                    file_edate     : File.getAttribute( 'e_date' ),
                    file_url       : File.getAttribute( 'cache_url' ),
                    file_dimension : dimension,
                    file_md5       : File.getAttribute( 'md5hash' ),
                    file_sha1      : File.getAttribute( 'sha1hash' ),

                    file_size : QUI.lib.Utils.formatSize(
                        File.getAttribute('filesize'), 2
                    )
                },
                this.$Panel.getBody().getElement( 'form' )
            );

            this.$Panel.setOptions({
                icon  : icon,
                title : File.getAttribute( 'file' )
            });

            this.$Panel.refresh();

            (function()
            {
                this.firstChild().click();
            }).delay( 100, CategoryBar );
        },

        unload : function()
        {

        },

        /**
         * Refresh the panel
         */
        refresh : function()
        {
            this.$Panel.Loader.show();
            this.$File.refresh(function()
            {
                var ButtonBar = this.$Panel.getButtonBar();

                this.$createButtons();
                this.$createTabs();

                this.load();

                this.$Panel.Loader.hide();

            }.bind( this ));
        },

        /**
         * Return the file objectwhich is linked to the panel
         *
         * @method QUI.controls.projects.media.Panel#load
         * @return {QUI.classesl.project.media.Item}
         */
        getFile : function()
        {
            return this.$File;
        },

        /**
         * Saves the files
         *
         * @method QUI.controls.projects.media.Panel#save
         */
        save : function()
        {
            var File = this.$File,
                Body = this.$Panel.getBody(),
                Frm  = Body.getElement( 'form' );

            if ( !Frm ) {
                return;
            }

            var data = QUI.lib.Utils.getFormData( Frm );

            File.setAttribute( 'name',  data.file_name );
            File.setAttribute( 'title', data.file_title );
            File.setAttribute( 'alt',   data.file_alt );
            File.setAttribute( 'short', data.file_short );

            this.$Panel.Loader.show();

            File.save(function(result, Request)
            {
                QUI.MH.addSuccess( 'Datei wurde erfolgreich gespeichert' );

                Request.getAttribute( 'Control' ).$Panel.Loader.hide();

            }, {
                Control : this
            });
        },

        /**
         * Delete the files
         *
         * @method QUI.controls.projects.media.Panel#del
         */
        del : function()
        {
            QUI.Windows.create('submit', {

                icon  : URL_BIN_DIR +'16x16/trashcan_empty.png',
                title : 'Möchten Sie '+ this.$File.getAttribute('file') +' wirklich löschen?',

                text     : 'Möchten Sie '+ this.$File.getAttribute('file') +' wirklich löschen?',
                texticon : URL_BIN_DIR +'32x32/trashcan_empty.png',

                information : 'Die Datei wird in den Papierkorb verschoben und kann wieder hergestellt werden.',
                autoclose   : true,
                Control     : this,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        Win.getAttribute( 'Control' )
                           .getFile()
                           .del(function(result, Request)
                            {
                               Request.getAttribute( 'Control' ).$Panel.close();
                               Request.getAttribute( 'Win' ).close();
                            }, {
                                Win     : Win,
                                Control : Win.getAttribute( 'Control' )
                            });
                    }
                }
            });
        },

        /**
         * Activate the file
         *
         * @method QUI.controls.projects.media.Panel#activate
         */
        activate : function()
        {
            this.$Panel.getButtonBar()
                       .getElement( 'status' )
                       .setAttribute( 'textimage', URL_BIN_DIR +'images/loader.gif' );

            this.$File.activate( this.refresh.bind( this ) );
        },

        /**
         * Deactivate the file
         *
         * @method QUI.controls.projects.media.Panel#activate
         */
        deactivate : function()
        {
            this.$Panel.getButtonBar()
                       .getElement( 'status' )
                       .setAttribute( 'textimage', URL_BIN_DIR +'images/loader.gif' );

            this.$File.deactivate( this.refresh.bind( this ) );
        },

        /**
         * Open the replace Dialog for the File
         *
         * @method QUI.controls.projects.media.Panel#replace
         */
        replace : function()
        {
            this.$DOMEvents.replace( this.$Panel.getBody() );
        },

        /**
         * Create the Buttons for the Panel
         * Such like Save, Delete
         *
         * @method QUI.controls.projects.media.FilePanel#$createTabs
         */
        $createButtons : function()
        {
            this.$Panel.getButtonBar().clear();

            this.$Panel.addButton(
                new QUI.controls.buttons.Button({
                    text      : 'Speichern',
                    textimage : URL_BIN_DIR +'16x16/save.png',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute( 'Control' ).save();
                        }
                    }
                })
            ).addButton(
                new QUI.controls.buttons.Button({
                    text      : 'Löschen',
                    textimage : URL_BIN_DIR +'16x16/trashcan_empty.png',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute( 'Control' ).del();
                        }
                    }
                })
            ).addButton(
                new QUI.controls.buttons.Button({
                    text      : 'Ersetzen mit ...',
                    textimage : URL_BIN_DIR +'16x16/replace.png',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Btn) {
                            Btn.getAttribute( 'Control' ).replace();
                        }
                    }
                })
            ).addButton(
                new QUI.controls.buttons.Seperator()
            );


            if ( this.$File.isActive() )
            {
                this.$Panel.addButton(
                    new QUI.controls.buttons.Button({
                        name      : 'status',
                        text      : 'Deaktivieren',
                        textimage : URL_BIN_DIR +'16x16/deactive.png',
                        Control   : this,
                        events    :
                        {
                            onClick : function(Btn) {
                                Btn.getAttribute( 'Control' ).deactivate();
                            }
                        }
                    })
                );
            } else
            {
                this.$Panel.addButton(
                    new QUI.controls.buttons.Button({
                        name      : 'status',
                        text      : 'Aktivieren',
                        textimage : URL_BIN_DIR +'16x16/active.png',
                        Control   : this,
                        events    :
                        {
                            onClick : function(Btn) {
                                Btn.getAttribute( 'Control' ).activate();
                            }
                        }
                    })
                );
            }
        },

        /**
         * Create the Tabs for the Panel
         * Such like Preview and Details Tab
         *
         * @method QUI.controls.projects.media.FilePanel#$createTabs
         */
        $createTabs : function()
        {
            this.$Panel.getCategoryBar().clear();

            this.$Panel.addCategory(
                new QUI.controls.buttons.Button({
                    text    : 'Datei Details',
                    name    : 'details',
                    Control : this,
                    icon    : URL_BIN_DIR +'22x22/details.png',
                    events  :
                    {
                        onActive : function(Tab) {
                            Tab.getAttribute( 'Control' ).$openDetails();
                        },

                        onNormal : function(Tab)
                        {
                            var Control = Tab.getAttribute( 'Control' ),
                                Panel   = Control.$Panel,
                                Body    = Panel.getBody();

                            Body.getElement( '.qui-media-file-details' )
                                .setStyle( 'display', 'none' );
                        }
                    }
                })
            ).addCategory(
                new QUI.controls.buttons.Button({
                    text    : 'Vorschau',
                    name    : 'preview',
                    icon    : URL_BIN_DIR +'22x22/preview.png',
                    Control : this,
                    events  :
                    {
                        onActive : function(Tab)
                        {
                            var Control = Tab.getAttribute( 'Control' ),
                                Panel   = Control.$Panel,
                                Body    = Panel.getBody(),
                                Preview = Body.getElement('.qui-media-file-preview');

                            Preview.setStyle( 'display', '' );
                            Preview.set( 'html', '' );

                            new Element('img', {
                                src    : URL_DIR + Control.$File.getAttribute( 'url' ),
                                styles : {
                                    margin : 20
                                }
                            }).inject( Preview );
                        },

                        onNormal : function(Tab)
                        {
                            var Control = Tab.getAttribute( 'Control' ),
                                Panel   = Control.$Panel,
                                Body    = Panel.getBody();

                            Body.getElement( '.qui-media-file-preview' )
                                .setStyle( 'display', 'none' );
                        }
                    }
                })
            );

        },

        /**
         * Opens the detail tab
         *
         * @method QUI.controls.projects.media.FilePanel#$createTabs
         */
        $openDetails : function()
        {
            var Panel = this.$Panel,
                Body  = Panel.getBody();

            Body.getElement('.qui-media-file-details').setStyle('display', '');

            // open button
            var Inp = Body.getElement('input[name="file_url"]');

            if ( Inp && typeof this.$OpenInNewWindow === 'undefined')
            {
                this.$OpenInNewWindow = new QUI.controls.buttons.Button({
                    name    : 'show_file',
                    image   : URL_BIN_DIR +'16x16/preview.png',
                    title   : 'Datei öffnen',
                    alt     : 'Datei öffnen',
                    Control : this,
                    events  :
                    {
                        onClick : function(Btn)
                        {
                            Btn.getAttribute('Control').
                                getFile().
                                openInWindow();
                        }
                    },
                    styles : {
                        'float' : 'right'
                    }
                });

                this.$OpenInNewWindow.inject( Inp, 'after' );
            }

            if ( Inp && typeof this.$Download === 'undefined' )
            {
                this.$Download = new QUI.controls.buttons.Button({
                    name    : 'download_file',
                    image   : URL_BIN_DIR +'16x16/down.png',
                    title   : 'Datei herunterladen',
                    alt     : 'Datei herunterladen',
                    Control : this,
                    events  :
                    {
                        onClick : function(Btn)
                        {
                            Btn.getAttribute('Control').
                                getFile().
                                download();
                        }
                    },
                    styles : {
                        'float' : 'right'
                    }
                });

                this.$Download.inject( Inp, 'after' );
            }
        }
    });

    return QUI.controls.projects.media.FilePanel;
});