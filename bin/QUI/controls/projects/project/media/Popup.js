/**
 * A media Popup
 *
 * @author www.namerobot.com (Henning Leutz)
 */

define('controls/projects/project/media/Popup', [

    'qui/controls/windows/Popup',
    'qui/controls/buttons/Button',
    'controls/projects/project/media/Panel',
    'Projects'

], function(QUIPopup, QUIButton, MediaPanel, Projects)
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
            closeButtonText : 'Abbrechen',

            selectable           : true,
            selectable_types     : false,   // you can specified which types are selectable
            selectable_mimetypes : false  	// you can specified which mime types are selectable
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Panel      = null;
            this.$folderData = false;

            this.addEvent( 'onCreate', this.$onCreate );
        },

        /**
         * event : on create
         */
        $onCreate : function()
        {
            this.Loader.show();

            var Media, Project;

            var self    = this,
                project = this.getAttribute( 'project' );

            if ( !project ) {
                project = Projects.Standard.getName();
            }

            Project = Projects.get( project );
            Media   = Project.getMedia();

            this.addButton(
                new QUIButton({
                    text : 'übernehmen',
                    textimage : 'icon-ok',
                    events :
                    {
                        onClick : function()
                        {
                            self.close();
                            self.fireEvent( 'submit', [ self, self.$folderData ] );
                        }
                    }
                })
            );

            this.$Panel = new MediaPanel(Media, {
                selectable           : true,
                selectable_types     : this.getAttribute( 'selectable_types' ),
                selectable_mimetypes : this.getAttribute( 'selectable_mimetypes' ),
                events :
                {
                    onCreate : function() {
                        self.Loader.hide();
                    },

                    onChildClick : function(Popup, imageData)
                    {
                        if ( imageData.type == 'folder' )
                        {
                            self.$Panel.openID( imageData.id );
                            self.$folderData = imageData;
                            return;
                        }

                        self.close();
                        self.fireEvent( 'submit', [ self, imageData ] );
                    }
                }
            });

            this.$Panel.inject( this.getContent() );
        }
    });

});