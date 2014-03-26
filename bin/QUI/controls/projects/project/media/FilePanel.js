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
 */

define('controls/projects/project/media/FilePanel', [

    'qui/controls/desktop/Panel',
    'classes/projects/project/media/panel/DOMEvents',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/windows/Confirm',
    'utils/Template',
    'qui/utils/Form',
    'utils/Controls',

    'css!controls/projects/project/media/FilePanel.css'

], function(QUIPanel, PanelDOMEvents, QUIButton, QUIButtonSeperator, QUIConfirm, Template, FormUtils, ControlUtils)
{
    "use strict";

    /**
     * A Media-Panel, opens the Media in an Desktop Panel
     *
     * @class controls/projects/project/media/FilePanel
     *
     * @param {classes/projects/media/File} File
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
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
         * @method controls/projects/project/media/FilePanel#close
         */
        close : function()
        {
            this.destroy();
        },

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

                ControlUtils.parse( Body.getElement( 'form' ) );
            });
        },

        /**
         * Load the buttons and the tabs to the panel
         *
         * @method controls/projects/project/media/FilePanel#load
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
         * @method controls/projects/project/media/FilePanel#unload
         */
        unload : function()
        {

        },

        /**
         * Refresh the panel
         *
         * @method controls/projects/project/media/FilePanel#refresh
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
         * @method controls/projects/project/media/FilePanel#load
         * @return {classes/project/media/Item} File
         */
        getFile : function()
        {
            return this.$File;
        },

        /**
         * Saves the files
         *
         * @method controls/projects/project/media/FilePanel#save
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

            var data = FormUtils.getFormData( Frm );

            File.setAttribute( 'name',  data.file_name );
            File.setAttribute( 'title', data.file_title );
            File.setAttribute( 'alt',   data.file_alt );
            File.setAttribute( 'short', data.file_short );

            this.Loader.show();

            File.save(function(result, Request)
            {
                QUI.getMessageHandler(function(MH) {
                    MH.addSuccess( 'Datei wurde erfolgreich gespeichert' );
                });

                self.Loader.hide();
            });
        },

        /**
         * Delete the files
         *
         * @method controls/projects/project/media/FilePanel#del
         */
        del : function()
        {
            var self = this;

            new QUIConfirm({
                icon  : 'icon-trash',
                title : 'Möchten Sie '+ this.$File.getAttribute('file') +' wirklich löschen?',

                text     : 'Möchten Sie '+ this.$File.getAttribute('file') +' wirklich löschen?',
                texticon : 'icon-trash',

                information : 'Die Datei wird in den Papierkorb verschoben und kann wieder hergestellt werden.',
                autoclose   : true,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        self.getFile().del(function(result, Request)
                        {
                           self.close();
                           Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         * Activate the file
         *
         * @method controls/projects/project/media/FilePanel#activate
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
         * @method controls/projects/project/media/FilePanel#activate
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
         * @method controls/projects/project/media/FilePanel#replace
         */
        replace : function()
        {
            this.$DOMEvents.replace( this.getBody() );
        },

        /**
         * Create the Buttons for the Panel
         * Such like Save, Delete
         *
         * @method controls/projects/project/media/FilePanel#$createTabs
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
         * @method controls/projects/project/media/FilePanel#$createTabs
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
         * @method controls/projects/project/media/FilePanel#$createTabs
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
                            window.open( self.getFile().getAttribute( 'cache_url' ) );
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