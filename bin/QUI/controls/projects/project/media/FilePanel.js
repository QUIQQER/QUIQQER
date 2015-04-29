
/**
 * Displays a Media in a Panel
 *
 * @module controls/projects/project/media/FilePanel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requir qui/QUI
 * @requir qui/controls/desktop/Panel
 * @requir classes/projects/project/media/panel/DOMEvents
 * @requir qui/controls/buttons/Button
 * @requir qui/controls/buttons/Seperator
 * @requir qui/controls/windows/Confirm
 * @requir utils/Template
 * @requir qui/utils/Form
 * @requir utils/Controls
 * @requir Locale
 * @requir css!controls/projects/project/media/FilePanel.css
 */

define('controls/projects/project/media/FilePanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'classes/projects/project/media/panel/DOMEvents',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/windows/Confirm',
    'utils/Template',
    'qui/utils/Form',
    'utils/Controls',
    'Locale',

    'css!controls/projects/project/media/FilePanel.css'

], function()
{
    "use strict";

    var lg = 'quiqqer/system';

    var QUI				   = arguments[ 0 ],
        QUIPanel           = arguments[ 1 ],
        PanelDOMEvents     = arguments[ 2 ],
        QUIButton          = arguments[ 3 ],
        QUIButtonSeperator = arguments[ 4 ],
        QUIConfirm         = arguments[ 5 ],
        Template           = arguments[ 6 ],
        FormUtils          = arguments[ 7 ],
        ControlUtils       = arguments[ 8 ],
        Locale             = arguments[ 9 ];

    /**
     * A Media-Panel, opens the Media in an Desktop Panel
     *
     * @class controls/projects/project/media/FilePanel
     *
     * @param {Object} File - classes/projects/media/File
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/media/FilePanel',

        Binds : [
            '$onCreate',
            '$onDestroy'
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
                onCreate  : this.$onCreate,
                onDestroy : this.$onDestroy
            });
        },

        /**
         * Return the Media object of the panel
         *
         * @method controls/projects/project/media/FilePanel#getMedia
         * @return {Object} Media (classes/projects/project/Media)
         */
        getMedia : function()
        {
            return this.$Media;
        },

        /**
         * Return the Project object of the Media
         *
         * @return {Object} Project (classes/projects/Project)
         */
        getProject : function()
        {
            return this.$Media.getProject();
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

            this.setAttribute( 'title', this.$File.getAttribute( 'file' ) );
            this.setAttribute( 'icon', this.$File.getAttribute( 'icon' ) );

            this.$createTabs();
            this.$createButtons();

            this.$File.addEvents({
                onSave : function() {
                    self.refresh();
                }
            });

            Template.get('project_media_file', function(result)
            {
                var Body = self.getContent();

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
         * @event : on panel destroy
         */
        $onDestroy : function()
        {

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
                icon        = 'fa fa-picture-o icon-picture',
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

            this.$File.refresh().then(function()
            {
                self.$createButtons();
                self.$createTabs();

                self.load();
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

            File.save(function()
            {
                QUI.getMessageHandler(function(MH) {
                    MH.addSuccess(
                        Locale.get( lg, 'projects.project.site.media.filePanel.message.save.success' )
                    );
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
                icon     : 'fa fa-trash-o icon-trash',
                texticon : 'fa fa-trash-o icon-trash',

                title : Locale.get( 'quiqqer/system', 'projects.project.site.media.filePanel.window.delete.title', {
                    file : this.$File.getAttribute('file')
                }),

                text : Locale.get( 'quiqqer/system', 'projects.project.site.media.filePanel.window.delete.text', {
                    file : this.$File.getAttribute('file')
                }),

                information : Locale.get( 'quiqqer/system', 'projects.project.site.media.filePanel.window.delete.text' ),
                autoclose   : true,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        self.getFile().del(function()
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
                .setAttribute( 'textimage', 'icon-spinner icon-spin' );

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
                .setAttribute( 'textimage', 'icon-spinner icon-spin' );

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
                    text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.save.text' ),
                    textimage : 'icon-save',
                    events    :
                    {
                        onClick : function() {
                            self.save();
                        }
                    }
                })
            ).addButton(
                new QUIButton({
                    text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.delete.text' ),
                    textimage : 'fa fa-trash-o icon-trash',
                    events    :
                    {
                        onClick : function() {
                            self.del();
                        }
                    }
                })
            ).addButton(
                new QUIButton({
                    text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.replace.text' ),
                    textimage : 'icon-upload',
                    events    :
                    {
                        onClick : function() {
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
                        text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.deactivate.text' ),
                        textimage : 'icon-remove',
                        Control   : this,
                        events    :
                        {
                            onClick : function() {
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
                        text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.activate.text' ),
                        textimage : 'icon-remove',
                        Control   : this,
                        events    :
                        {
                            onClick : function() {
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
                    text    : Locale.get( lg, 'projects.project.site.media.filePanel.details.text' ),
                    name    : 'details',
                    Control : this,
                    icon    : 'fa fa-file-o icon-file-alt',
                    events  :
                    {
                        onActive : function() {
                            self.$openDetails();
                        },

                        onNormal : function()
                        {
                            var Body = self.getContent();

                            Body.getElement( '.qui-media-file-details' )
                                .setStyle( 'display', 'none' );
                        }
                    }
                })
            ).addCategory(
                new QUIButton({
                    text    : Locale.get( lg, 'projects.project.site.media.filePanel.preview.text' ),
                    name    : 'preview',
                    icon    : 'fa fa-eye icon-eye-open',
                    Control : this,
                    events  :
                    {
                        onActive : function()
                        {
                            var Body    = self.getContent(),
                                Preview = Body.getElement('.qui-media-file-preview');

                            Preview.setStyle( 'display', '' );
                            Preview.set( 'html', '' );

                            var url = URL_DIR + self.$File.getAttribute( 'url' );

                            if (url.match('image.php')) {
                                url = url +'&noresize=1';
                            }

                            new Element('img', {
                                src    : url,
                                styles : {
                                    maxWidth : '100%'
                                }
                            }).inject( Preview );
                        },

                        onNormal : function()
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

//            if ( Inp && typeof this.$OpenInNewWindow === 'undefined')
//            {
//                this.$OpenInNewWindow = new QUIButton({
//                    name    : 'show_file',
//                    image   : 'fa fa-eye',
//                    title   : Locale.get( lg, 'projects.project.site.media.filePanel.btn.openFile.title' ),
//                    alt     : Locale.get( lg, 'projects.project.site.media.filePanel.btn.openFile.alt' ),
//                    events  :
//                    {
//                        onClick : function(Btn) {
//                            window.open( self.getFile().getAttribute( 'cache_url' ) );
//                        }
//                    },
//                    styles : {
//                        'float' : 'right'
//                    }
//                });
//
//                if ( this.$File.getAttribute( 'active' ) ) {
//                    this.$OpenInNewWindow.disable();
//                }
//
//                this.$OpenInNewWindow.inject( Inp, 'after' );
//            }

            if ( Inp && typeof this.$Download === 'undefined' )
            {
                this.$Download = new QUIButton({
                    name   : 'download_file',
                    image  : 'icon-download',
                    title  : Locale.get( lg, 'projects.project.site.media.filePanel.btn.downloadFile.title' ),
                    alt    : Locale.get( lg, 'projects.project.site.media.filePanel.btn.downloadFile.alt' ),
                    events :
                    {
                        onClick : function() {
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