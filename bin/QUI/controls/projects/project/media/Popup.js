
/**
 * A media Popup
 *
 * @module controls/projects/project/media/Popup
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Popup
 * @require qui/controls/buttons/Button
 * @require controls/projects/project/media/Panel
 * @require Projects
 * @require Locale
 * @require Ajax
 */

define('controls/projects/project/media/Popup', [

    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'controls/projects/project/media/Panel',
    'Projects',
    'Locale',
    'Ajax'

], function(QUIPopup, QUIConfirm, QUIButton, MediaPanel, Projects, QUILocale, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIPopup,
        Type    : 'controls/projects/project/media/Popup',

        Binds : [
            '$onCreate'
        ],

        options : {
            project         : false,
            fileid          : false,
            closeButtonText : QUILocale.get('quiqqer/system', 'cancel'),

            selectable           : true,
            selectable_types     : false,   // you can specified which types are selectable
            selectable_mimetypes : false  	// you can specified which mime types are selectable
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Panel      = null;
            this.$folderData = false;

            this.addEvent('onCreate', this.$onCreate);
            this.addEvent('onClose', function()
            {
                this.$Panel.destroy();
            }.bind(this));
        },

        /**
         * event : on create
         */
        $onCreate : function()
        {
            this.Loader.show();

            var Media, Project, Content;

            var self    = this,
                project = this.getAttribute( 'project' );

            if ( !project ) {
                project = Projects.Standard.getName();
            }

            Project = Projects.get( project );
            Media   = Project.getMedia();
            Content = this.getContent();

            this.addButton(
                new QUIButton({
                    text      : QUILocale.get( 'quiqqer/system', 'accept' ),
                    textimage : 'fa fa-check icon-ok',
                    events    :
                    {
                        onClick : function()
                        {
                            self.$getDetails(self.$folderData, function(data) {
                                self.$submit( data, true );
                            });
                        }
                    }
                })
            );

            Content.setStyles({
                padding : 0
            });

            Ajax.get('ajax_media_file_getParentId', function(parentId)
            {
                self.$Panel = new MediaPanel(Media, {
                    startid     : parentId,
                    dragable    : false,
                    collapsible : false,
                    selectable  : true,
                    selectable_types     : self.getAttribute( 'selectable_types' ),
                    selectable_mimetypes : self.getAttribute( 'selectable_mimetypes' ),
                    events :
                    {
                        onCreate : function(Panel)
                        {
                            Panel.getElm().setStyle( 'borderRadius', 0 );
                            self.Loader.hide();
                        },

                        onChildClick : function(Panel, imageData) {
                            self.$itemClick( imageData );
                        }
                    }
                });

                self.$Panel.inject( Content );

            }, {
                fileid  : this.getAttribute( 'fileid' ),
                project : Project.getName()
            });
        },

        /**
         * If item is inactive
         * @param {Object} imageData - data of the image
         */
        $activateItem : function(imageData)
        {
            var self = this;

            this.close();

            var Confirm = new QUIConfirm({
                title       : QUILocale.get( 'quiqqer/system', 'projects.project.site.media.popup.window.activate.title' ),
                text        : QUILocale.get( 'quiqqer/system', 'projects.project.site.media.popup.window.activate.text' ),
                information : QUILocale.get( 'quiqqer/system', 'projects.project.site.media.popup.window.activate.information' ),
                autoclose   : false,
                events :
                {
                    onCancel : function()
                    {
                        require([
                            'controls/projects/project/media/Popup'
                        ], function(MediaPopup)
                        {
                            var MP = new MediaPopup( self.getAttributes() );

                            if ( "submit" in self.$events )
                            {
                                self.$events.submit.each(function(f) {
                                    MP.addEvent( 'submit', f );
                                });
                            }

                            MP.open();
                        });
                    },

                    onSubmit : function(Win)
                    {
                        // activate file
                        Win.Loader.show();

                        Ajax.post('ajax_media_activate', function()
                        {
                            Win.close();
                            self.$submit( imageData, true );
                        }, {
                            project : imageData.project,
                            fileid  : imageData.id
                        });
                    }
                }
            });

            (function() {
                Confirm.open();
            }).delay( 500 );
        },

        /**
         * submit
         * @param {Object} imageData      - data of the image
         * @param {Boolean} [folderCheck] - (optional) make folder submit check?
         */
        $submit : function(imageData, folderCheck)
        {
            folderCheck = folderCheck || false;

            if ( typeof imageData === 'undefined' ) {
                return;
            }

            // if folder is in the selectable_types, than you can select the folder
            if ( folderCheck )
            {
                var folders = this.getAttribute( 'selectable_types' );

                if ( folders && folders.contains( 'folder' ) )
                {
                    this.close();
                    this.fireEvent( 'submit', [ this, imageData ] );
                    return;
                }
            }


            if ( imageData.type == 'folder' )
            {
                this.$Panel.openID( imageData.id );
                this.$folderData = imageData;
                return;
            }

            this.close();
            this.fireEvent( 'submit', [ this, imageData ] );
        },

        /**
         * event : click on item
         * @param {Object} imageData -  data of the image
         */
        $itemClick : function(imageData)
        {
            var self = this;

            this.$Panel.Loader.hide();

            this.$getDetails(imageData, function(data)
            {
                if ( !( data.active ).toInt() )
                {
                    self.$Panel.Loader.hide();
                    self.$activateItem( imageData );
                    return;
                }

                self.$submit( imageData );
            });
        },

        /**
         *
         * @param imageData
         */
        $getDetails : function(imageData, callback)
        {
            Ajax.get('ajax_media_details', callback, {
                project : imageData.project,
                fileid  : imageData.id
            });
        }
    });
});
