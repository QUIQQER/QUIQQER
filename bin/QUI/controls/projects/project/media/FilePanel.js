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

define('controls/projects/project/media/FilePanel', [

    'qui/controls/desktop/Panel',
    'classes/projects/project/media/panel/DOMEvents',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'utils/Template',
    'qui/utils/Form',

    'css!controls/projects/project/media/FilePanel.css'

], function(QUIControl, PanelDOMEvents, QUIButton, QUIButtonSeperator, Template, FormUtils)
{
    "use strict";

    /**
     * A Media-Panel, opens the Media in an Desktop Panel
     *
     * @class QUI.controls.projects.media.FilePanel
     *
     * @param {QUI.classes.projects.media.File} File
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/media/FilePanel',

        Binds : [
            '$onCreate'
        ],

        options : {
            id        : 'projects-media-file-panel',
            container : false,
            fileid    : false
        },

        initialize : function(File, options)
        {
            // default id
            this.setAttribute(
                'id',
                'projects-media-file-panel-'+ File.getId()
            );

            this.setAttribute(
                'name',
                'projects-media-file-panel-'+ File.getId()
            );

            this.parent( options );

            this.$Panel = null;
            this.$File  = File;
            this.$Media = this.$File.getMedia();

            this.$DOMEvents = new PanelDOMEvents( this );

            this.addEvents({
                onCreate : this.$onCreate
            });
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
         * create a QUI.controls.desktop.Panel and start the file loading
         *
         * @method QUI.controls.projects.media.FilePanel#create
         */
//        create : function()
//        {
//            var Panel = new QUI.controls.desktop.Panel({
//                id     : this.getAttribute( 'id' ),
//                icon   : URL_BIN_DIR +'images/loader.gif',
//                tabbar : true
//            });
//
//            QUI.Workspace.appendPanel( Panel );
//
//            this.$Panel = Panel;
//            this.$Panel.Loader.show();
//            this.$Panel.getBody().set( 'data-id', this.$File.getId() );
//
//            this.$createTabs();
//            this.$createButtons();
//
//            QUI.Template.get('project_media_file', function(result, Request)
//            {
//                var FormElm;
//
//                var Control = Request.getAttribute( 'Control' ),
//                    Panel   = Control.$Panel,
//                    File    = Control.$File,
//                    Body    = Panel.getBody();
//
//                Body.set(
//                    'html',
//
//                    '<form>' +
//                        result +
//                        '<div class="qui-media-file-preview"></div>' +
//                    '</form>'
//                );
//
//                FormElm = Body.getElement( 'form' );
//
//                QUI.controls.Utils.parse( FormElm );
//
//                Control.refresh();
//            }, {
//                Control : this
//            });
//        },

        /**
         * @event : on panel create
         */
        $onCreate : function()
        {
            var self = this;

            this.Loader.show();
            this.getContent().set( 'data-id', this.$File.getId() );

            this.setAttribute(
                'title',
                this.$File.getAttribute( 'file' )
            );

            this.setAttribute(
                'icon',
                this.$File.getAttribute( 'icon' )
            );

            this.$createTabs();
            this.$createButtons();

            Template.get('project_media_file', function(result, Request)
            {
                var File = self.$File,
                    Body = self.getContent();

                Body.set(
                  'html',

                  '<form>'+
                      result +
                      '<div class="qui-media-file-preview"></div>' +
                  '</form>'
                );

                self.load();

//                QUI.controls.Utils.parse( Body.getElement( 'form' ) );
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
                icon        = 'icon-picture',
                CategoryBar = this.getCategoryBar();

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
            FormUtils.setDataToForm({
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
                    file_size      : File.getAttribute('filesize')
                },
                this.getContent().getElement( 'form' )
            );

            this.setOptions({
                icon  : icon,
                title : File.getAttribute( 'file' )
            });

            (function()
            {
                this.firstChild().click();

            }).delay( 100, CategoryBar );

            this.Loader.hide();
        },

        /**
         * Unload the panel
         *
         * @method QUI.controls.projects.media.FilePanel#unload
         */
        unload : function()
        {

        },

        /**
         * Refresh the panel
         *
         * @method QUI.controls.projects.media.FilePanel#refresh
         */
        refresh : function()
        {
            var self = this;

            this.Loader.show();

            self.$File.refresh(function()
            {
                var ButtonBar = self.getButtonBar();

                self.$createButtons();
                self.$createTabs();

                self.load();

                self.Loader.hide();
            });
        },

        /**
         * Return the file objectwhich is linked to the panel
         *
         * @method QUI.controls.projects.media.Panel#load
         * @return {QUI.classesl.project.media.Item} File
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
            var self = this,
                File = this.$File,
                Body = this.getContent(),
                Frm  = Body.getElement( 'form' );

            if ( !Frm ) {
                return;
            }

            var data = QUI.Utils.getFormData( Frm );

            File.setAttribute( 'name',  data.file_name );
            File.setAttribute( 'title', data.file_title );
            File.setAttribute( 'alt',   data.file_alt );
            File.setAttribute( 'short', data.file_short );

            this.Loader.show();

            File.save(function(result, Request)
            {
                QUI.MH.addSuccess( 'Datei wurde erfolgreich gespeichert' );

                self.Loader.hide();

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
            this.getButtonBar()
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
            this.getButtonBar()
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
            var self = this;

            this.getButtonBar().clear();

            this.addButton(
                new QUIButton({
                    text      : 'Speichern',
                    textimage : 'icon-save',
                    events    :
                    {
                        onClick : function(Btn) {
                            self.save();
                        }
                    }
                })
            ).addButton(
                new QUIButton({
                    text      : 'Löschen',
                    textimage : 'icon-trash',
                    events    :
                    {
                        onClick : function(Btn) {
                            self.del();
                        }
                    }
                })
            ).addButton(
                new QUIButton({
                    text      : 'Ersetzen mit ...',
                    textimage : 'icon-upload',
                    events    :
                    {
                        onClick : function(Btn) {
                            self.replace();
                        }
                    }
                })
            ).addButton(
                new QUIButtonSeperator()
            );


            if ( this.$File.isActive() )
            {
                this.addButton(
                    new QUIButton({
                        name      : 'status',
                        text      : 'Deaktivieren',
                        textimage : 'icon-remove',
                        Control   : this,
                        events    :
                        {
                            onClick : function(Btn) {
                                self.deactivate();
                            }
                        }
                    })
                );
            } else
            {
                this.addButton(
                    new QUIButton({
                        name      : 'status',
                        text      : 'Aktivieren',
                        textimage : 'icon-remove',
                        Control   : this,
                        events    :
                        {
                            onClick : function(Btn) {
                                self.activate();
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
            var self = this;

            this.getCategoryBar().clear();

            this.addCategory(
                new QUIButton({
                    text    : 'Datei Details',
                    name    : 'details',
                    Control : this,
                    icon    : URL_BIN_DIR +'22x22/details.png',
                    events  :
                    {
                        onActive : function(Tab) {
                            self.$openDetails();
                        },

                        onNormal : function(Tab)
                        {
                            var Body = self.getContent();

                            Body.getElement( '.qui-media-file-details' )
                                .setStyle( 'display', 'none' );
                        }
                    }
                })
            ).addCategory(
                new QUIButton({
                    text    : 'Vorschau',
                    name    : 'preview',
                    icon    : URL_BIN_DIR +'22x22/preview.png',
                    Control : this,
                    events  :
                    {
                        onActive : function(Tab)
                        {
                            var Body    = self.getContent(),
                                Preview = Body.getElement('.qui-media-file-preview');

                            Preview.setStyle( 'display', '' );
                            Preview.set( 'html', '' );

                            new Element('img', {
                                src    : URL_DIR + self.$File.getAttribute( 'url' ),
                                styles : {
                                    margin : 20
                                }
                            }).inject( Preview );
                        },

                        onNormal : function(Tab)
                        {
                            var Body = self.getContent();

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
            var self = this,
                Body = this.getContent();

            Body.getElement('.qui-media-file-details').setStyle('display', '');

            // open button
            var Inp = Body.getElement('input[name="file_url"]');

            if ( Inp && typeof this.$OpenInNewWindow === 'undefined')
            {
                this.$OpenInNewWindow = new QUIButton({
                    name    : 'show_file',
                    image   : URL_BIN_DIR +'16x16/preview.png',
                    title   : 'Datei öffnen',
                    alt     : 'Datei öffnen',
                    events  :
                    {
                        onClick : function(Btn) {
                            self.getFile().openInWindow();
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
                this.$Download = new QUIButton({
                    name    : 'download_file',
                    image   : URL_BIN_DIR +'16x16/down.png',
                    title   : 'Datei herunterladen',
                    alt     : 'Datei herunterladen',
                    events  :
                    {
                        onClick : function(Btn) {
                            self.getFile().download();
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
});